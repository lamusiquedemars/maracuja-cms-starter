<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('display_name')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('email')->nullable();
            $table->string('normalized_email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('normalized_phone')->nullable()->index();
            $table->string('locale', 16)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('source', 40)->nullable()->index();
            $table->timestamps();
        });

        if (Schema::hasTable('audience_contacts')) {
            Schema::table('audience_contacts', function (Blueprint $table): void {
                $table->foreignId('contact_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('contacts')
                    ->nullOnDelete();
            });

            DB::table('audience_contacts')
                ->orderBy('id')
                ->get()
                ->each(function (object $audienceContact): void {
                    $contactId = $this->resolveContactId([
                        'first_name' => $audienceContact->first_name,
                        'last_name' => $audienceContact->last_name,
                        'organization_name' => $audienceContact->organization_name ?? null,
                        'email' => $audienceContact->email,
                        'source' => 'audience',
                    ]);

                    DB::table('audience_contacts')
                        ->where('id', $audienceContact->id)
                        ->update(['contact_id' => $contactId]);
                });
        }

        if (Schema::hasTable('inquiries')) {
            Schema::table('inquiries', function (Blueprint $table): void {
                $table->foreignId('contact_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('contacts')
                    ->nullOnDelete();
            });

            DB::table('inquiries')
                ->orderBy('id')
                ->get()
                ->each(function (object $inquiry): void {
                    $contactId = $this->resolveContactId([
                        'display_name' => $inquiry->name,
                        'email' => $inquiry->email,
                        'phone' => $inquiry->phone,
                        'source' => 'inquiry',
                    ]);

                    DB::table('inquiries')
                        ->where('id', $inquiry->id)
                        ->update(['contact_id' => $contactId]);
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inquiries') && Schema::hasColumn('inquiries', 'contact_id')) {
            Schema::table('inquiries', fn (Blueprint $table) => $table->dropConstrainedForeignId('contact_id'));
        }

        if (Schema::hasTable('audience_contacts') && Schema::hasColumn('audience_contacts', 'contact_id')) {
            Schema::table('audience_contacts', fn (Blueprint $table) => $table->dropConstrainedForeignId('contact_id'));
        }

        Schema::dropIfExists('contacts');
    }

    /**
     * @param  array<string, ?string>  $attributes
     */
    private function resolveContactId(array $attributes): int
    {
        $email = $this->clean($attributes['email'] ?? null);
        $normalizedEmail = $email !== null ? mb_strtolower($email) : null;
        $phone = $this->clean($attributes['phone'] ?? null);
        $normalizedPhone = $phone !== null ? preg_replace('/[^\d+]/', '', $phone) : null;

        $existingId = $normalizedEmail !== null
            ? DB::table('contacts')->where('normalized_email', $normalizedEmail)->value('id')
            : null;

        if ($existingId === null && filled($normalizedPhone)) {
            $existingId = DB::table('contacts')->where('normalized_phone', $normalizedPhone)->value('id');
        }

        if ($existingId !== null) {
            return (int) $existingId;
        }

        return (int) DB::table('contacts')->insertGetId([
            'first_name' => $this->clean($attributes['first_name'] ?? null),
            'last_name' => $this->clean($attributes['last_name'] ?? null),
            'display_name' => $this->clean($attributes['display_name'] ?? null),
            'organization_name' => $this->clean($attributes['organization_name'] ?? null),
            'email' => $email,
            'normalized_email' => $normalizedEmail,
            'phone' => $phone,
            'normalized_phone' => filled($normalizedPhone) ? $normalizedPhone : null,
            'source' => $this->clean($attributes['source'] ?? null),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function clean(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
};
