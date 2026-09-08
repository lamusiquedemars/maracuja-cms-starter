<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acquisition_deliveries', function (Blueprint $table): void {
            $table->dropForeign(['inquiry_id']);
            $table->foreignId('inquiry_id')->nullable()->change();
            $table->foreign('inquiry_id')->references('id')->on('inquiries')->nullOnDelete();
        });
    }

    public function down(): void {}
};
