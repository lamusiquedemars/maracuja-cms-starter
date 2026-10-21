<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audience_contacts') || Schema::hasColumn('audience_contacts', 'unsubscribe_token')) {
            return;
        }

        Schema::table('audience_contacts', function (Blueprint $table): void {
            $table->string('unsubscribe_token', 64)->nullable()->unique()->after('accepts_email');
        });

        DB::table('audience_contacts')
            ->whereNull('unsubscribe_token')
            ->orderBy('id')
            ->eachById(function (object $contact): void {
                DB::table('audience_contacts')
                    ->where('id', $contact->id)
                    ->update(['unsubscribe_token' => Str::random(48)]);
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('audience_contacts') || ! Schema::hasColumn('audience_contacts', 'unsubscribe_token')) {
            return;
        }

        Schema::table('audience_contacts', function (Blueprint $table): void {
            $table->dropUnique(['unsubscribe_token']);
            $table->dropColumn('unsubscribe_token');
        });
    }
};
