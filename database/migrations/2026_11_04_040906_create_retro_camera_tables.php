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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('event_type')->default('wedding'); // wedding, church, general
            $table->date('event_date')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('theme_preset')->default('ivory_gold'); // ivory_gold, sacred_olive, classic_film
            $table->string('filter_preset')->default('fuji_quicksnap'); // fuji_quicksnap, kodak_funsaver, warm_portra
            $table->unsignedInteger('max_shots_per_device')->default(36);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_gallery_public')->default(true);
            $table->timestamps();
        });

        Schema::create('camera_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('device_token');
            $table->string('guest_name')->nullable();
            $table->unsignedInteger('shots_taken')->default(0);
            $table->unsignedInteger('max_shots')->default(36);
            $table->unsignedInteger('reset_count')->default(0);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_shot_at')->nullable();
            $table->timestamp('last_reset_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'device_token']);
        });

        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('camera_session_id')->constrained('camera_sessions')->cascadeOnDelete();
            $table->unsignedInteger('shot_number')->default(1);
            $table->string('original_path')->nullable();
            $table->string('file_path');
            $table->string('thumbnail_path')->nullable();
            $table->string('camera_facing')->default('environment'); // environment, user
            $table->boolean('flash_enabled')->default(false);
            $table->string('filter_applied')->default('fuji_quicksnap');
            $table->string('caption')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamp('taken_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('photos');
        Schema::dropIfExists('camera_sessions');
        Schema::dropIfExists('events');
    }
};
