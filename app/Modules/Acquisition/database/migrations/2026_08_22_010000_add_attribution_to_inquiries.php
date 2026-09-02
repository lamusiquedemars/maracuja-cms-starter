<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table): void {
            $table->string('attribution_source')->nullable()->after('source');
            $table->string('attribution_medium')->nullable()->after('attribution_source');
            $table->string('attribution_campaign')->nullable()->after('attribution_medium');
            $table->json('attribution_first_touch')->nullable()->after('attribution_campaign');
            $table->json('attribution_last_touch')->nullable()->after('attribution_first_touch');
            $table->string('attribution_method', 32)->nullable()->after('attribution_last_touch');
            $table->decimal('attribution_confidence', 3, 2)->nullable()->after('attribution_method');
            $table->index(['attribution_source', 'attribution_campaign']);
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table): void {
            $table->dropIndex(['attribution_source', 'attribution_campaign']);
            $table->dropColumn([
                'attribution_source', 'attribution_medium', 'attribution_campaign',
                'attribution_first_touch', 'attribution_last_touch', 'attribution_method', 'attribution_confidence',
            ]);
        });
    }
};
