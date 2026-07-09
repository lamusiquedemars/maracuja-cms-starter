<?php

namespace App\Modules\Audience\Services;

use App\Modules\Audience\Exceptions\BrevoAudienceException;
use App\Modules\Audience\Models\AudienceContact;
use App\Modules\Audience\Models\AudienceBrevoSetting;
use App\Modules\Audience\Models\AudienceSegment;
use App\Modules\Audience\Models\SegmentMessage;
use App\Modules\Audience\Models\SegmentMessageDelivery;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class BrevoAudienceService
{
    private const BASE_URL = 'https://api.brevo.com/v3';
    private const DEFAULT_FOLDER_NAME = 'Maracuja';

    /**
     * @return array{ok: bool, status: string, message: string}
     */
    public function testConnection(AudienceBrevoSetting $setting): array
    {
        if (! $setting->hasApiKey()) {
            return [
                'ok' => false,
                'status' => AudienceBrevoSetting::TEST_STATUS_MISSING_KEY,
                'message' => 'Aucune clé API Brevo n’est enregistrée.',
            ];
        }

        try {
            $response = Http::withHeaders([
                'api-key' => (string) $setting->api_key_encrypted,
                'accept' => 'application/json',
            ])
                ->timeout(10)
                ->get(self::BASE_URL . '/account');
        } catch (ConnectionException $exception) {
            return [
                'ok' => false,
                'status' => AudienceBrevoSetting::TEST_STATUS_FAILED,
                'message' => 'Connexion Brevo impossible: ' . $exception->getMessage(),
            ];
        }

        if ($response->successful()) {
            return [
                'ok' => true,
                'status' => AudienceBrevoSetting::TEST_STATUS_SUCCESS,
                'message' => 'Connexion Brevo validée.',
            ];
        }

        return [
            'ok' => false,
            'status' => AudienceBrevoSetting::TEST_STATUS_FAILED,
            'message' => 'Brevo a refuse la connexion (' . $response->status() . ').',
        ];
    }

    /**
     * @return array{targeted: int, synced: int, failed: int, excluded: int, list_id: int}
     */
    public function syncSegment(AudienceSegment $segment, ?AudienceBrevoSetting $setting = null): array
    {
        $setting ??= AudienceBrevoSetting::current();

        if (! $setting->is_enabled || ! $setting->hasApiKey()) {
            throw new RuntimeException('Brevo doit être activé et configuré avant la synchronisation.');
        }

        $segment->forceFill([
            'brevo_sync_status' => 'syncing',
            'brevo_sync_error' => null,
        ])->save();

        try {
            $listId = $this->ensureSegmentList($segment, $setting);
            $contacts = $this->eligibleContacts($segment);
            $synced = 0;
            $failed = 0;

            foreach ($contacts as $contact) {
                try {
                    $this->upsertContact($contact, $listId, $setting);

                    $contact->forceFill([
                        'brevo_synced_at' => now(),
                        'brevo_sync_status' => 'synced',
                        'brevo_sync_error' => null,
                    ])->save();

                    $synced++;
                } catch (RequestException|ConnectionException|RuntimeException $exception) {
                    $contact->forceFill([
                        'brevo_sync_status' => 'failed',
                        'brevo_sync_error' => Str::limit($exception->getMessage(), 1000),
                    ])->save();

                    $failed++;
                }
            }

            $segment->forceFill([
                'brevo_list_id' => $listId,
                'brevo_synced_at' => now(),
                'brevo_sync_status' => $failed > 0 ? 'partial' : 'synced',
                'brevo_sync_error' => $failed > 0 ? "{$failed} contact(s) en erreur lors de la synchronisation." : null,
            ])->save();

            return [
                'targeted' => $segment->contacts()->count(),
                'synced' => $synced,
                'failed' => $failed,
                'excluded' => max(0, $segment->contacts()->count() - $contacts->count()),
                'list_id' => $listId,
            ];
        } catch (RequestException|ConnectionException|RuntimeException $exception) {
            $segment->forceFill([
                'brevo_sync_status' => 'failed',
                'brevo_sync_error' => Str::limit($exception->getMessage(), 1000),
            ])->save();

            throw $exception;
        }
    }

    public function ensureSegmentList(AudienceSegment $segment, AudienceBrevoSetting $setting): int
    {
        if ($segment->brevo_list_id) {
            return (int) $segment->brevo_list_id;
        }

        $folderId = $this->ensureFolder($setting);
        $listName = $this->segmentListName($segment);

        $existingListId = $this->findListIdByName($listName, $setting);

        if ($existingListId !== null) {
            $segment->forceFill(['brevo_list_id' => $existingListId])->save();

            return $existingListId;
        }

        $response = $this->client($setting)
            ->post(self::BASE_URL . '/contacts/lists', [
                'name' => $listName,
                'folderId' => $folderId,
            ])
            ->throw();

        $listId = (int) $response->json('id');

        if ($listId <= 0) {
            throw new RuntimeException('Brevo n’a pas renvoyé d’identifiant de liste.');
        }

        $segment->forceFill(['brevo_list_id' => $listId])->save();

        return $listId;
    }

    public function segmentListName(AudienceSegment $segment): string
    {
        return 'Maracuja - ' . $segment->name;
    }

    public function createCampaign(SegmentMessage $message, ?AudienceBrevoSetting $setting = null): int
    {
        $setting ??= AudienceBrevoSetting::current();

        if (! $message->usesBrevo()) {
            throw new RuntimeException('Cette campagne n’utilise pas le canal Brevo.');
        }

        if ($message->brevo_campaign_id) {
            return (int) $message->brevo_campaign_id;
        }

        if (! $setting->is_enabled || ! $setting->hasApiKey()) {
            throw new RuntimeException('Brevo doit être activé et configuré avant la création de campagne.');
        }

        if (blank($setting->sender_email)) {
            throw new RuntimeException('L’email expéditeur Brevo doit être renseigné.');
        }

        if ($message->hasPublicImageWarnings()) {
            throw new RuntimeException('Une ou plusieurs images du message ne sont pas accessibles publiquement. Publiez le site ou remplacez les images avant de créer la campagne Brevo.');
        }

        $message->forceFill([
            'status' => SegmentMessage::STATUS_SYNCING_TO_BREVO,
            'brevo_error' => null,
        ])->save();

        try {
            $syncStats = $this->syncSegment($message->segment, $setting);
            $listId = $syncStats['list_id'];

            if ($syncStats['synced'] <= 0) {
                throw new RuntimeException('Aucun contact éligible n’a pu être synchronisé vers Brevo.');
            }

            $snapshotHtml = $message->bodyForEmail();
            $sender = [
                'name' => $setting->sender_name ?: config('maracuja.product_name', 'Maracuja CMS'),
                'email' => $setting->sender_email,
            ];

            $payload = [
                'name' => $this->campaignName($message),
                'sender' => $sender,
                'subject' => $message->subject,
                'htmlContent' => $snapshotHtml,
                'recipients' => [
                    'listIds' => [$listId],
                ],
            ];

            if (filled($setting->reply_to_email)) {
                $payload['replyTo'] = $setting->reply_to_email;
            }

            $response = $this->client($setting)
                ->post(self::BASE_URL . '/emailCampaigns', $payload)
                ->throw();

            $campaignId = (int) $response->json('id');

            if ($campaignId <= 0) {
                throw new RuntimeException('Brevo n’a pas renvoyé d’identifiant de campagne.');
            }

            $message->forceFill([
                'status' => SegmentMessage::STATUS_CREATED_IN_BREVO,
                'brevo_campaign_id' => $campaignId,
                'brevo_status' => 'draft',
                'brevo_created_at' => now(),
                'brevo_last_sync_at' => now(),
                'brevo_error' => null,
                'content_snapshot_html' => $snapshotHtml,
                'subject_snapshot' => $message->subject,
                'sender_snapshot' => $sender + [
                    'reply_to_email' => $setting->reply_to_email,
                ],
            ])->save();

            return $campaignId;
        } catch (RequestException|ConnectionException|RuntimeException $exception) {
            $userException = $this->friendlyException($exception);

            $message->forceFill([
                'status' => SegmentMessage::STATUS_SYNC_FAILED,
                'brevo_error' => Str::limit($userException->technicalMessage(), 1000),
                'brevo_last_sync_at' => now(),
            ])->save();

            throw $userException;
        }
    }

    public function campaignName(SegmentMessage $message): string
    {
        return 'Maracuja #' . $message->id . ' - ' . $message->subject;
    }

    public function sendCampaign(SegmentMessage $message, ?AudienceBrevoSetting $setting = null): void
    {
        $setting ??= AudienceBrevoSetting::current();

        if (! $message->usesBrevo()) {
            throw new RuntimeException('Cette campagne n’utilise pas le canal Brevo.');
        }

        if (! $message->brevo_campaign_id) {
            throw new RuntimeException('La campagne doit d’abord être créée dans Brevo.');
        }

        if ($message->status === SegmentMessage::STATUS_SENT_TO_PROVIDER) {
            return;
        }

        $message->forceFill([
            'status' => SegmentMessage::STATUS_SENDING,
            'brevo_error' => null,
        ])->save();

        try {
            $this->client($setting)
                ->post(self::BASE_URL . '/emailCampaigns/' . $message->brevo_campaign_id . '/sendNow')
                ->throw();

            $this->markCampaignSentToProvider($message);
        } catch (RequestException|ConnectionException|RuntimeException $exception) {
            $userException = $this->friendlyException($exception);

            $message->forceFill([
                'status' => SegmentMessage::STATUS_SYNC_FAILED,
                'brevo_error' => Str::limit($userException->technicalMessage(), 1000),
                'brevo_last_sync_at' => now(),
            ])->save();

            throw $userException;
        }
    }

    private function markCampaignSentToProvider(SegmentMessage $message): void
    {
        $contacts = $this->eligibleContacts($message->segment);

        foreach ($contacts as $contact) {
            SegmentMessageDelivery::query()->updateOrCreate(
                [
                    'segment_message_id' => $message->id,
                    'audience_contact_id' => $contact->id,
                ],
                [
                    'email' => $contact->email,
                    'status' => SegmentMessageDelivery::STATUS_SENT_TO_PROVIDER,
                    'provider_status' => 'sent_to_provider',
                    'latest_event' => 'sent_to_provider',
                    'latest_event_at' => now(),
                    'sent_at' => now(),
                    'error_message' => null,
                ],
            );

            $contact->forceFill(['last_contacted_at' => now()])->save();
        }

        $message->forceFill([
            'status' => SegmentMessage::STATUS_SENT_TO_PROVIDER,
            'brevo_status' => 'sent_to_provider',
            'brevo_sent_at' => now(),
            'brevo_last_sync_at' => now(),
            'brevo_error' => null,
            'recipients_count' => $contacts->count(),
            'sent_at' => now(),
        ])->save();
    }

    private function ensureFolder(AudienceBrevoSetting $setting): int
    {
        if ($setting->default_folder_id) {
            return (int) $setting->default_folder_id;
        }

        $existingFolderId = $this->findFolderIdByName(self::DEFAULT_FOLDER_NAME, $setting);

        if ($existingFolderId !== null) {
            $setting->forceFill(['default_folder_id' => $existingFolderId])->save();

            return $existingFolderId;
        }

        $response = $this->client($setting)
            ->post(self::BASE_URL . '/contacts/folders', [
                'name' => self::DEFAULT_FOLDER_NAME,
            ])
            ->throw();

        $folderId = (int) $response->json('id');

        if ($folderId <= 0) {
            throw new RuntimeException('Brevo n’a pas renvoyé d’identifiant de dossier.');
        }

        $setting->forceFill(['default_folder_id' => $folderId])->save();

        return $folderId;
    }

    private function findFolderIdByName(string $name, AudienceBrevoSetting $setting): ?int
    {
        $folders = $this->client($setting)
            ->get(self::BASE_URL . '/contacts/folders', [
                'limit' => 50,
                'offset' => 0,
            ])
            ->throw()
            ->json('folders', []);

        return $this->findIdByName($folders, $name);
    }

    private function findListIdByName(string $name, AudienceBrevoSetting $setting): ?int
    {
        $lists = $this->client($setting)
            ->get(self::BASE_URL . '/contacts/lists', [
                'limit' => 50,
                'offset' => 0,
            ])
            ->throw()
            ->json('lists', []);

        return $this->findIdByName($lists, $name);
    }

    private function findIdByName(array $items, string $name): ?int
    {
        foreach ($items as $item) {
            if (($item['name'] ?? null) === $name && isset($item['id'])) {
                return (int) $item['id'];
            }
        }

        return null;
    }

    /**
     * @return Collection<int, AudienceContact>
     */
    private function eligibleContacts(AudienceSegment $segment): Collection
    {
        return $segment->contacts()
            ->where('accepts_email', true)
            ->whereNull('unsubscribed_at')
            ->whereNull('hard_bounced_at')
            ->whereNull('email_blacklisted_at')
            ->orderBy('audience_contacts.id')
            ->get()
            ->filter(fn (AudienceContact $contact): bool => filter_var($contact->email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique(fn (AudienceContact $contact): string => Str::lower($contact->email))
            ->values();
    }

    private function upsertContact(AudienceContact $contact, int $listId, AudienceBrevoSetting $setting): void
    {
        $payload = [
            'email' => $contact->email,
            'attributes' => $this->contactAttributes($contact),
            'listIds' => [$listId],
        ];

        $createResponse = $this->client($setting)
            ->post(self::BASE_URL . '/contacts', $payload);

        if ($createResponse->successful()) {
            return;
        }

        if ($createResponse->status() !== 400) {
            $createResponse->throw();
        }

        $this->client($setting)
            ->put(self::BASE_URL . '/contacts/' . rawurlencode($contact->email), [
                'attributes' => $payload['attributes'],
                'listIds' => [$listId],
            ])
            ->throw();
    }

    private function contactAttributes(AudienceContact $contact): array
    {
        return collect([
            'FNAME' => $contact->first_name,
            'LNAME' => $contact->last_name,
        ])
            ->filter(fn (?string $value): bool => filled($value))
            ->all();
    }

    private function friendlyException(\Throwable $exception): BrevoAudienceException
    {
        if ($exception instanceof BrevoAudienceException) {
            return $exception;
        }

        if ($exception instanceof RuntimeException && ! $exception instanceof RequestException) {
            return new BrevoAudienceException($exception->getMessage(), $exception->getMessage());
        }

        $message = $exception->getMessage();
        $technicalMessage = $message;

        if ($exception instanceof RequestException && $exception->response !== null) {
            $technicalMessage = trim($message . "\n" . $exception->response->body());
            $brevoMessage = (string) $exception->response->json('message', '');

            if ($brevoMessage !== '') {
                $technicalMessage .= "\n" . $brevoMessage;
            }
        }

        if (str_contains($technicalMessage, 'Sender is invalid / inactive')) {
            return new BrevoAudienceException(
                'L’expéditeur configuré n’est pas encore validé dans Brevo. Vérifiez dans Brevo que l’adresse expéditrice est activée, puis relancez la création.',
                $technicalMessage,
            );
        }

        if (str_contains($technicalMessage, 'method_not_allowed') && str_contains($technicalMessage, 'tag option')) {
            return new BrevoAudienceException(
                'Brevo a refusé une option de campagne non disponible sur ce compte. Le réglage a été ajusté, vous pouvez relancer la création.',
                $technicalMessage,
            );
        }

        return new BrevoAudienceException(
            'Brevo a refusé la création de la campagne. Vérifiez la configuration Brevo, puis relancez la création.',
            $technicalMessage,
        );
    }

    private function client(AudienceBrevoSetting $setting): PendingRequest
    {
        return Http::withHeaders([
            'api-key' => (string) $setting->api_key_encrypted,
            'accept' => 'application/json',
        ])
            ->asJson()
            ->timeout(20);
    }
}
