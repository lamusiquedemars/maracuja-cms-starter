<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'contact_id' => fn (Blueprint $table) => $table->foreignId('contact_id')->nullable()->constrained('contacts')->nullOnDelete(),
            'consent_at' => fn (Blueprint $table) => $table->timestamp('consent_at')->nullable(),
            'source' => fn (Blueprint $table) => $table->string('source', 80)->default('website_contact_form')->index(),
            'attribution_source' => fn (Blueprint $table) => $table->string('attribution_source')->nullable(),
            'attribution_medium' => fn (Blueprint $table) => $table->string('attribution_medium')->nullable(),
            'attribution_campaign' => fn (Blueprint $table) => $table->string('attribution_campaign')->nullable(),
            'attribution_first_touch' => fn (Blueprint $table) => $table->json('attribution_first_touch')->nullable(),
            'attribution_last_touch' => fn (Blueprint $table) => $table->json('attribution_last_touch')->nullable(),
            'attribution_method' => fn (Blueprint $table) => $table->string('attribution_method')->nullable(),
            'attribution_confidence' => fn (Blueprint $table) => $table->decimal('attribution_confidence', 4, 2)->nullable(),
        ];

        foreach ($columns as $name => $addColumn) {
            if (! Schema::hasColumn('inquiries', $name)) {
                Schema::table('inquiries', $addColumn);
            }
        }
    }

    public function down(): void
    {
        // Historical inquiries predate the Core schema. Keep the compatible fields on rollback.
    }
};
