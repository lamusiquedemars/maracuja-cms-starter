<?php

namespace Tests\Unit;

use App\Modules\Audience\Actions\ImportAudienceContactsFromCsv;
use App\Modules\Audience\Models\AudienceContact;
use App\Modules\Audience\Models\AudienceSegment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportAudienceContactsFromCsvTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_contacts_and_segments_from_csv(): void
    {
        $path = $this->csvPath(<<<'CSV'
email,first_name,last_name,organization_name,accepts_email,segments,notes
alice@example.test,Alice,Durand,Conservatoire de Lyon,1,"Tous les clients;Clients en location","Location violon"
bernard@example.test,Bernard,Martin,École de musique,oui,"Tous les clients","Client atelier"
invalid-email,Claire,Petit,Association,1,"Tous les clients","Email invalide"
CSV);

        $result = ImportAudienceContactsFromCsv::run($path);

        $this->assertSame(2, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame(2, AudienceContact::query()->count());
        $this->assertSame(2, AudienceSegment::query()->count());

        $alice = AudienceContact::query()->where('email', 'alice@example.test')->firstOrFail();

        $this->assertSame('Conservatoire de Lyon', $alice->organization_name);
        $this->assertNotNull($alice->unsubscribe_token);
        $this->assertTrue($alice->segments()->where('name', 'Tous les clients')->exists());
        $this->assertTrue($alice->segments()->where('name', 'Clients en location')->exists());
    }

    public function test_it_updates_existing_contacts_and_adds_default_segment(): void
    {
        $contact = AudienceContact::query()->create([
            'first_name' => 'Ancien',
            'email' => 'alice@example.test',
            'accepts_email' => false,
        ]);

        $path = $this->csvPath(<<<'CSV'
email;prenom;nom;organisation;consentement
alice@example.test;Alice;Durand;Conservatoire;oui
CSV);

        $result = ImportAudienceContactsFromCsv::run($path, 'Tous les clients');

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame('Alice', $contact->refresh()->first_name);
        $this->assertSame('Conservatoire', $contact->organization_name);
        $this->assertTrue($contact->accepts_email);
        $this->assertTrue($contact->segments()->where('name', 'Tous les clients')->exists());
    }

    public function test_it_does_not_clear_existing_fields_when_csv_only_contains_segments(): void
    {
        $contact = AudienceContact::query()->create([
            'first_name' => 'Alice',
            'last_name' => 'Durand',
            'organization_name' => 'Conservatoire',
            'email' => 'alice@example.test',
            'accepts_email' => false,
        ]);

        $path = $this->csvPath(<<<'CSV'
email,segments
alice@example.test,"Clients en location"
CSV);

        ImportAudienceContactsFromCsv::run($path);

        $contact->refresh();

        $this->assertSame('Alice', $contact->first_name);
        $this->assertSame('Durand', $contact->last_name);
        $this->assertSame('Conservatoire', $contact->organization_name);
        $this->assertFalse($contact->accepts_email);
        $this->assertTrue($contact->segments()->where('name', 'Clients en location')->exists());
    }

    private function csvPath(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'audience-csv-');
        file_put_contents($path, $contents);

        return $path;
    }
}
