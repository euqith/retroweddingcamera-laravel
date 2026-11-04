<?php

namespace Tests\Feature;

use App\Models\CameraSession;
use App\Models\Event;
use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RetroCameraTest extends TestCase
{
    use RefreshDatabase;

    protected Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->event = Event::create([
            'title' => 'The Wedding of Daniel & Clara',
            'slug' => 'daniel-clara',
            'event_type' => 'wedding',
            'event_date' => now()->format('Y-m-d'),
            'location' => 'St. Mary Cathedral',
            'theme_preset' => 'ivory_gold',
            'filter_preset' => 'fuji_quicksnap',
            'max_shots_per_device' => 36,
            'is_active' => true,
            'is_gallery_public' => true,
        ]);
    }

    public function test_guest_can_access_event_landing_page(): void
    {
        $response = $this->get('/e/daniel-clara');
        $response->assertStatus(200);
        $response->assertSee('The Wedding of Daniel & Clara');
        $response->assertSee('Buka Kamera Disposable');
    }

    public function test_guest_can_access_camera_page(): void
    {
        $response = $this->get('/e/daniel-clara/camera');
        $response->assertStatus(200);
        $response->assertSee('Disposable Camera');
        $response->assertSee('36 Exposures');
    }

    public function test_guest_can_capture_photo_and_quota_decrements(): void
    {
        // 1x1 transparent GIF base64
        $dummyBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->postJson('/e/daniel-clara/camera/capture', [
            'image' => $dummyBase64,
            'camera_facing' => 'environment',
            'flash_enabled' => false,
        ], [
            'X-Device-Token' => 'TEST-DEVICE-01',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'shots_taken' => 1,
            'max_shots' => 36,
            'remaining' => 35,
            'is_roll_empty' => false,
        ]);

        $this->assertDatabaseHas('photos', [
            'event_id' => $this->event->id,
            'shot_number' => 1,
        ]);

        $this->assertDatabaseHas('camera_sessions', [
            'event_id' => $this->event->id,
            'device_token' => 'TEST-DEVICE-01',
            'shots_taken' => 1,
        ]);
    }

    public function test_guest_cannot_exceed_36_shots_per_device(): void
    {
        // Pre-create session with 36 shots used
        $session = CameraSession::create([
            'event_id' => $this->event->id,
            'device_token' => 'EXHAUSTED-DEVICE',
            'shots_taken' => 36,
            'max_shots' => 36,
        ]);

        $dummyBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->postJson('/e/daniel-clara/camera/capture', [
            'image' => $dummyBase64,
        ], [
            'X-Device-Token' => 'EXHAUSTED-DEVICE',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'remaining' => 0,
        ]);
    }

    public function test_device_roll_can_be_reset(): void
    {
        $session = CameraSession::create([
            'event_id' => $this->event->id,
            'device_token' => 'RESET-TEST-DEVICE',
            'shots_taken' => 36,
            'max_shots' => 36,
        ]);

        $response = $this->postJson('/e/daniel-clara/session/reset', [], [
            'X-Device-Token' => 'RESET-TEST-DEVICE',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'shots_taken' => 0,
            'remaining' => 36,
        ]);

        $this->assertDatabaseHas('camera_sessions', [
            'id' => $session->id,
            'shots_taken' => 0,
            'reset_count' => 1,
        ]);
    }

    public function test_guest_can_view_gallery(): void
    {
        $response = $this->get('/e/daniel-clara/gallery');
        $response->assertStatus(200);
        $response->assertSee('Public Event Gallery');
    }

    public function test_admin_can_access_qrcodes(): void
    {
        $response = $this->get("/admin/events/{$this->event->id}/qrcodes");
        $response->assertStatus(200);
        $response->assertSee('Buka Kamera Disposable');
        $response->assertSee('Buka Galeri Foto');
    }
}
