<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('google_drive_sync_frequency', 50)->default('daily')->after('google_drive_sync_status');
            $table->timestamp('google_drive_last_scheduled_sync_at')->nullable()->after('google_drive_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['google_drive_sync_frequency', 'google_drive_last_scheduled_sync_at']);
        });
    }
};
