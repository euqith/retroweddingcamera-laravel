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

    public function test_captured_photo_is_stored_in_event_slug_folder(): void
    {
        $dummyBase64 = 'data:image/png;base64,' . base64_encode(
            imagepng(imagecreatetruecolor(50, 50)) ? ob_get_clean() : ''
        );

        $response = $this->postJson('/e/daniel-clara/camera/capture', [
            'image' => $dummyBase64,
            'camera_facing' => 'environment',
            'flash_enabled' => false,
        ]);

        $response->assertStatus(200);
        $photo = Photo::first();
        $this->assertNotNull($photo);
        $this->assertStringStartsWith('photos/daniel-clara/', $photo->file_path);
        Storage::disk('public')->assertExists($photo->file_path);
    }

    public function test_deleting_event_removes_photo_folder_and_files_from_server(): void
    {
        $dummyBase64 = 'data:image/png;base64,' . base64_encode(
            imagepng(imagecreatetruecolor(50, 50)) ? ob_get_clean() : ''
        );

        $this->postJson('/e/daniel-clara/camera/capture', [
            'image' => $dummyBase64,
            'camera_facing' => 'environment',
            'flash_enabled' => false,
        ]);

        $photo = Photo::first();
        Storage::disk('public')->assertExists($photo->file_path);

        // Delete the event
        $response = $this->delete("/admin/events/{$this->event->id}");
        $response->assertRedirect('/admin');

        // Verify folder and files on disk are removed
        Storage::disk('public')->assertMissing($photo->file_path);
        Storage::disk('public')->assertMissing($photo->thumbnail_path);
        $this->assertEmpty(Storage::disk('public')->allFiles("photos/daniel-clara"));
        $this->assertDatabaseMissing('events', ['id' => $this->event->id]);
        $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
    }

    public function test_admin_can_delete_single_photo(): void
    {
        $dummyBase64 = 'data:image/png;base64,' . base64_encode(
            imagepng(imagecreatetruecolor(50, 50)) ? ob_get_clean() : ''
        );

        $this->postJson('/e/daniel-clara/camera/capture', [
            'image' => $dummyBase64,
            'camera_facing' => 'environment',
            'flash_enabled' => false,
        ]);

        $photo = Photo::first();
        $this->assertNotNull($photo);
        Storage::disk('public')->assertExists($photo->file_path);

        // Delete photo
        $response = $this->delete("/admin/photos/{$photo->id}");
        $response->assertRedirect();

        $this->assertDatabaseMissing('photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing($photo->file_path);
        Storage::disk('public')->assertMissing($photo->thumbnail_path);
    }

    public function test_admin_can_clear_all_event_photos(): void
    {
        $dummyBase64 = 'data:image/png;base64,' . base64_encode(
            imagepng(imagecreatetruecolor(50, 50)) ? ob_get_clean() : ''
        );

        $this->postJson('/e/daniel-clara/camera/capture', [
            'image' => $dummyBase64,
            'camera_facing' => 'environment',
            'flash_enabled' => false,
        ]);

        $this->assertDatabaseCount('photos', 1);

        // Clear all photos
        $response = $this->post("/admin/events/{$this->event->id}/gallery/clear");
        $response->assertRedirect();

        $this->assertDatabaseCount('photos', 0);
        $this->assertEmpty(Storage::disk('public')->allFiles("photos/daniel-clara"));
    }

    public function test_photo_streaming_route_serves_file_and_thumbnail(): void
    {
        $dummyBase64 = 'data:image/png;base64,' . base64_encode(
            imagepng(imagecreatetruecolor(50, 50)) ? ob_get_clean() : ''
        );

        $this->postJson('/e/daniel-clara/camera/capture', [
            'image' => $dummyBase64,
            'camera_facing' => 'environment',
            'flash_enabled' => false,
        ]);

        $photo = Photo::first();
        $this->assertNotNull($photo);

        $this->assertStringContainsString("/photos/view/{$photo->id}", $photo->url);
        $this->assertStringContainsString("/photos/thumb/{$photo->id}", $photo->thumbnail_url);

        $viewResponse = $this->get($photo->url);
        $viewResponse->assertStatus(200);

        $thumbResponse = $this->get($photo->thumbnail_url);
        $thumbResponse->assertStatus(200);
    }

    public function test_film_simulation_is_device_specific_and_does_not_change_event_default(): void
    {
        $dummyBase64 = 'data:image/png;base64,' . base64_encode(
            imagepng(imagecreatetruecolor(50, 50)) ? ob_get_clean() : ''
        );

        // Assert event starts with 'fuji_quicksnap' default
        $this->assertEquals('fuji_quicksnap', $this->event->filter_preset);

        // Device A captures photo with 'ilford_bw' simulation
        $resA = $this->postJson('/e/daniel-clara/camera/capture', [
            'image' => $dummyBase64,
            'filter_applied' => 'ilford_bw',
        ], [
            'X-Device-Token' => 'DEVICE-A-ILFORD',
        ]);
        $resA->assertStatus(200);

        // Check Device A's photo got 'ilford_bw'
        $photoA = Photo::find($resA->json('photo.id'));
        $this->assertNotNull($photoA);
        $this->assertEquals('ilford_bw', $photoA->filter_applied);

        // Confirm Event's default in database was NOT changed!
        $this->event->refresh();
        $this->assertEquals('fuji_quicksnap', $this->event->filter_preset);

        // Device B captures photo with 'kodak_funsaver'
        $resB = $this->postJson('/e/daniel-clara/camera/capture', [
            'image' => $dummyBase64,
            'filter_applied' => 'kodak_funsaver',
        ], [
            'X-Device-Token' => 'DEVICE-B-KODAK',
        ]);
        $resB->assertStatus(200);

        // Check Device B's photo got 'kodak_funsaver'
        $photoB = Photo::find($resB->json('photo.id'));
        $this->assertNotNull($photoB);
        $this->assertEquals('kodak_funsaver', $photoB->filter_applied);

        // Confirm Event's default STILL remains 'fuji_quicksnap'
        $this->event->refresh();
        $this->assertEquals('fuji_quicksnap', $this->event->filter_preset);

        // Device C captures without specifying filter_applied (falls back to event default)
        $resC = $this->postJson('/e/daniel-clara/camera/capture', [
            'image' => $dummyBase64,
        ], [
            'X-Device-Token' => 'DEVICE-C-DEFAULT',
        ]);
        $resC->assertStatus(200);

        $photoC = Photo::find($resC->json('photo.id'));
        $this->assertNotNull($photoC);
        $this->assertEquals('fuji_quicksnap', $photoC->filter_applied);
        $this->event->refresh();
        $this->assertEquals('fuji_quicksnap', $this->event->filter_preset);
    }
}

