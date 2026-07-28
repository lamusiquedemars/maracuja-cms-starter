<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
            $table->foreignId('conversation_id')
                ->nullable()
                ->after('contact_id')
                ->unique()
                ->constrained('conversations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('conversation_id');
            $table->string('email')->nullable(false)->change();
        });
    }
};
