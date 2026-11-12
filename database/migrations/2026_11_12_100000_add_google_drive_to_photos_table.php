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
        Schema::table('photos', function (Blueprint $table) {
            $table->string('google_drive_file_id', 150)->nullable()->after('filter_applied')->index();
            $table->timestamp('google_drive_synced_at')->nullable()->after('google_drive_file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropIndex(['google_drive_file_id']);
            $table->dropColumn(['google_drive_file_id', 'google_drive_synced_at']);
        });
    }
};
