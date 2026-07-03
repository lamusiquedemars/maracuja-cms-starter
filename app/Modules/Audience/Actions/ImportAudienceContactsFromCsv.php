<?php

namespace App\Modules\Audience\Actions;

use App\Modules\Audience\Models\AudienceContact;
use App\Modules\Audience\Models\AudienceSegment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ImportAudienceContactsFromCsv
{
    /**
     * @return array{created: int, updated: int, skipped: int, segments: int, errors: array<int, string>}
     */
    public static function run(string $path, ?string $defaultSegment = null): array
    {
        $fullPath = Storage::disk('local')->exists($path)
            ? Storage::disk('local')->path($path)
            : $path;

        if (! is_file($fullPath) || ! is_readable($fullPath)) {
            throw new RuntimeException('Le fichier CSV est introuvable ou illisible.');
        }

        $handle = fopen($fullPath, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Impossible d’ouvrir le fichier CSV.');
        }

        $firstLine = fgets($handle) ?: '';
        $delimiter = self::detectDelimiter($firstLine);
        rewind($handle);

        $headers = fgetcsv($handle, 0, $delimiter);

        if (! is_array($headers)) {
            fclose($handle);

            throw new RuntimeException('Le fichier CSV doit contenir une ligne d’en-têtes.');
        }

        $headers = array_map(fn (string $header): string => self::normalizeHeader($header), $headers);
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $segmentsCreated = 0;
        $errors = [];
        $lineNumber = 1;

        DB::transaction(function () use (
            $handle,
            $delimiter,
            $headers,
            $defaultSegment,
            &$created,
            &$updated,
            &$skipped,
            &$segmentsCreated,
            &$errors,
            &$lineNumber,
        ): void {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $lineNumber++;

                if (self::isBlankRow($row)) {
                    continue;
                }

                $data = self::combineRow($headers, $row);
                $email = mb_strtolower(trim((string) ($data['email'] ?? '')));

                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    $errors[] = "Ligne {$lineNumber}: email absent ou invalide.";

                    continue;
                }

                $contact = AudienceContact::query()
                    ->whereRaw('lower(email) = ?', [$email])
                    ->first();

                $attributes = ['email' => $email];
                $firstName = self::firstPresent($data, ['first_name', 'prenom']);
                $lastName = self::firstPresent($data, ['last_name', 'nom']);
                $organizationName = self::firstPresent($data, ['organization_name', 'organisation', 'structure']);
                $acceptsEmail = self::firstPresent($data, ['accepts_email', 'consentement', 'email_ok']);

                if ($firstName !== null) {
                    $attributes['first_name'] = self::nullableString($firstName);
                }

                if ($lastName !== null) {
                    $attributes['last_name'] = self::nullableString($lastName);
                }

                if ($organizationName !== null) {
                    $attributes['organization_name'] = self::nullableString($organizationName);
                }

                if (array_key_exists('notes', $data)) {
                    $attributes['notes'] = self::nullableString($data['notes']);
                }

                if ($acceptsEmail !== null || ! $contact) {
                    $attributes['accepts_email'] = self::toBoolean($acceptsEmail ?? true);
                }

                if ($contact) {
                    $contact->fill($attributes)->save();
                    $updated++;
                } else {
                    $contact = AudienceContact::query()->create($attributes);
                    $created++;
                }

                $segmentNames = self::segmentNames($data, $defaultSegment);
                $segmentIds = [];

                foreach ($segmentNames as $segmentName) {
                    $segment = AudienceSegment::query()->firstOrCreate(['name' => $segmentName]);

                    if ($segment->wasRecentlyCreated) {
                        $segmentsCreated++;
                    }

                    $segmentIds[] = $segment->id;
                }

                if ($segmentIds !== []) {
                    $contact->segments()->syncWithoutDetaching($segmentIds);
                }
            }
        });

        fclose($handle);

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'segments' => $segmentsCreated,
            'errors' => array_slice($errors, 0, 10),
        ];
    }

    private static function detectDelimiter(string $line): string
    {
        $candidates = [',', ';', "\t"];

        return collect($candidates)
            ->sortByDesc(fn (string $delimiter): int => substr_count($line, $delimiter))
            ->first() ?? ',';
    }

    private static function normalizeHeader(string $header): string
    {
        $header = trim(str_replace("\xEF\xBB\xBF", '', $header));
        $header = mb_strtolower($header);
        $header = str_replace([' ', '-', '.'], '_', $header);

        return match ($header) {
            'e_mail', 'courriel', 'mail' => 'email',
            'firstname', 'first', 'prenom', 'prénom' => 'first_name',
            'lastname', 'last', 'surname', 'nom_de_famille' => 'last_name',
            'organization', 'organisation', 'organisme', 'structure', 'company', 'societe', 'société' => 'organization_name',
            'segment', 'segments', 'groupes', 'group' => 'segments',
            'accepte_email', 'accepte_les_emails', 'optin', 'opt_in', 'consentement_email' => 'accepts_email',
            default => $header,
        };
    }

    /**
     * @param  array<int, string|null>  $row
     */
    private static function isBlankRow(array $row): bool
    {
        return trim(implode('', array_map(fn ($value): string => (string) $value, $row))) === '';
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string|null>  $row
     * @return array<string, string|null>
     */
    private static function combineRow(array $headers, array $row): array
    {
        $data = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $data[$header] = isset($row[$index]) ? trim((string) $row[$index]) : null;
        }

        return $data;
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, string|null>  $data
     * @param  array<int, string>  $keys
     */
    private static function firstPresent(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }

        return null;
    }

    private static function toBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = mb_strtolower(trim((string) $value));

        if ($value === '') {
            return true;
        }

        return in_array($value, ['1', 'true', 'yes', 'oui', 'o', 'y', 'ok'], true);
    }

    /**
     * @param  array<string, string|null>  $data
     * @return array<int, string>
     */
    private static function segmentNames(array $data, ?string $defaultSegment): array
    {
        $rawSegments = trim((string) ($data['segments'] ?? ''));
        $names = $rawSegments === '' ? [] : preg_split('/[;,|]+/', $rawSegments);

        if ($defaultSegment !== null && trim($defaultSegment) !== '') {
            $names[] = $defaultSegment;
        }

        return collect($names)
            ->map(fn (?string $name): string => trim((string) $name))
            ->filter()
            ->unique(fn (string $name): string => mb_strtolower($name))
            ->values()
            ->all();
    }
}
