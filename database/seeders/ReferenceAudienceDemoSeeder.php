<?php

namespace Database\Seeders;

use Maracuja\SiteCore\Contact\Models\Contact;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;
use Maracuja\SiteCore\Audience\Models\AudienceContact;
use Maracuja\SiteCore\Audience\Models\AudienceSegment;
use Maracuja\SiteCore\Audience\Models\SegmentMessage;
use Maracuja\SiteCore\Audience\Models\SegmentMessageDelivery;
use Maracuja\SiteCore\Brevo\Models\AudienceBrevoEvent;

final class ReferenceAudienceDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Le jeu de démonstration Audience est réservé aux environnements local et testing.');
        }

        DB::transaction(function (): void {
            $this->clearHistoricalAudienceData();

            $contacts = collect([
                ['first_name' => 'Claire', 'last_name' => 'Durand', 'email' => 'claire.durand@example.test', 'source' => 'demo'],
                ['first_name' => 'Louis', 'last_name' => 'Martin', 'email' => 'louis.martin@example.test', 'source' => 'demo'],
                ['first_name' => 'Nora', 'last_name' => 'Bernard', 'email' => 'nora.bernard@example.test', 'source' => 'demo'],
            ])->mapWithKeys(function (array $attributes): array {
                $contact = Contact::query()->firstOrCreate(
                    ['normalized_email' => $attributes['email']],
                    $attributes,
                );

                return [$attributes['email'] => $contact];
            });

            $clients = AudienceSegment::query()->create([
                'name' => 'Clients',
                'description' => 'Personnes déjà accompagnées.',
            ]);
            $professionnels = AudienceSegment::query()->create([
                'name' => 'Professionnels',
                'description' => 'Partenaires et prescripteurs.',
            ]);
            $newsletter = AudienceSegment::query()->create([
                'name' => 'Newsletter générale',
                'description' => 'Abonnés aux nouvelles du site.',
            ]);

            $claire = $this->subscribe($contacts['claire.durand@example.test'], [$clients, $newsletter]);
            $nora = $this->subscribe($contacts['nora.bernard@example.test'], [$professionnels, $newsletter]);

            SegmentMessage::query()->create([
                'audience_segment_id' => $newsletter->id,
                'subject' => 'Bienvenue dans la démonstration Maracuja',
                'body' => '<p>Cette campagne est un brouillon de démonstration. Aucun email ne sera envoyé.</p>',
                'provider' => 'brevo',
                'status' => SegmentMessage::STATUS_DRAFT,
                'recipients_count' => 0,
            ]);

            // Make the relationship explicit in the code: a subscriber is a
            // contact with additional marketing consent, never a duplicate.
            $claire->load('contact');
            $nora->load('contact');
        });
    }

    /** @param list<AudienceSegment> $segments */
    private function subscribe(Contact $contact, array $segments): AudienceContact
    {
        $subscriber = AudienceContact::query()->create([
            'first_name' => $contact->first_name,
            'last_name' => $contact->last_name,
            'email' => $contact->email,
            'accepts_email' => true,
            'consent_source' => 'demo',
            'consented_at' => now(),
        ]);

        $subscriber->segments()->sync(collect($segments)->pluck('id'));

        return $subscriber;
    }

    private function clearHistoricalAudienceData(): void
    {
        AudienceBrevoEvent::query()->delete();
        SegmentMessageDelivery::query()->delete();
        SegmentMessage::query()->delete();
        DB::table('audience_contact_segment')->delete();
        AudienceSegment::query()->delete();
        AudienceContact::query()->delete();
    }
}
