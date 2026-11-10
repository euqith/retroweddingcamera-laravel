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
            $table->string('google_drive_url', 500)->nullable()->after('is_gallery_public');
            $table->string('google_drive_folder_id', 150)->nullable()->after('google_drive_url');
            $table->string('google_drive_sync_status', 50)->default('idle')->after('google_drive_folder_id');
            $table->timestamp('google_drive_synced_at')->nullable()->after('google_drive_sync_status');
            $table->unsignedInteger('google_drive_synced_count')->default(0)->after('google_drive_synced_at');
            $table->text('google_drive_last_error')->nullable()->after('google_drive_synced_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'google_drive_url',
                'google_drive_folder_id',
                'google_drive_sync_status',
                'google_drive_synced_at',
                'google_drive_synced_count',
                'google_drive_last_error',
            ]);
        });
    }
};
