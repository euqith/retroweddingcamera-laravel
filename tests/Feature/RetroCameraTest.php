<?php

namespace Tests\Feature;

use App\Models\CameraSession;
use App\Models\Event;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RetroCameraTest extends TestCase
{
    use RefreshDatabase;

    protected Event $event;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->adminUser = User::where('name', 'adminretro')->first()
            ?? User::create([
                'name' => 'adminretro',
                'email' => 'adminretro@kurisu.id',
                'password' => bcrypt('Adm1n456'),
            ]);

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

    public function test_guest_can_capture_with_lomography_film_preset(): void
    {
        $dummyBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->postJson('/e/daniel-clara/camera/capture', [
            'image' => $dummyBase64,
            'camera_facing' => 'environment',
            'flash_enabled' => false,
            'filter_applied' => 'lomography_color',
        ], [
            'X-Device-Token' => 'LOMO-DEVICE-01',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('photos', [
            'event_id' => $this->event->id,
            'filter_applied' => 'lomography_color',
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

    public function test_unauthenticated_user_cannot_access_admin_dashboard_or_endpoints(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');

        $responseCreate = $this->get('/admin/events/create');
        $responseCreate->assertRedirect('/login');

        $responseDelete = $this->delete("/admin/events/{$this->event->id}");
        $responseDelete->assertRedirect('/login');
    }

    public function test_roll_reset_is_completely_removed_for_production(): void
    {
        $session = CameraSession::create([
            'event_id' => $this->event->id,
            'device_token' => 'RESET-TEST-DEVICE',
            'shots_taken' => 36,
            'max_shots' => 36,
        ]);

        // 1. Guest public endpoint is removed (404)
        $guestResponse = $this->postJson('/e/daniel-clara/session/reset', [], [
            'X-Device-Token' => 'RESET-TEST-DEVICE',
        ]);
        $guestResponse->assertStatus(404);

        // 2. Admin reset endpoint is also completely removed for production (404)
        $adminResponse = $this->actingAs($this->adminUser)->post("/admin/sessions/{$session->id}/reset");
        $adminResponse->assertStatus(404);

        // Roll remains at 36 (no accidental reset possible)
        $this->assertDatabaseHas('camera_sessions', [
            'id' => $session->id,
            'shots_taken' => 36,
        ]);
    }

    public function test_camera_is_blocked_when_event_has_not_started_yet(): void
    {
        // Event starts tomorrow
        $upcomingEvent = Event::create([
            'title' => 'Upcoming Wedding',
            'slug' => 'upcoming-wedding',
            'event_type' => 'wedding',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
            'theme_preset' => 'sacred_olive',
            'filter_preset' => 'lomography_color',
            'max_shots_per_device' => 36,
            'is_active' => true,
        ]);

        $this->assertEquals('upcoming', $upcomingEvent->cameraStatus());
        $this->assertFalse($upcomingEvent->isCameraOpen());

        // Camera page shows locked gate
        $response = $this->get('/e/upcoming-wedding/camera');
        $response->assertStatus(200);
        $response->assertSee('Kamera Belum Dibuka');
        $response->assertSee('Buka Galeri Foto Bersama Tamu');

        // Capture attempt is blocked (403)
        $captureResponse = $this->postJson('/e/upcoming-wedding/camera/capture', [
            'image' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ]);
        $captureResponse->assertStatus(403);
        $captureResponse->assertJson(['success' => false]);
    }

    public function test_camera_is_blocked_when_event_has_ended_but_gallery_remains_accessible(): void
    {
        // Event ended yesterday
        $endedEvent = Event::create([
            'title' => 'Past Wedding',
            'slug' => 'past-wedding',
            'event_type' => 'wedding',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
            'theme_preset' => 'classic_film',
            'filter_preset' => 'fuji_quicksnap',
            'max_shots_per_device' => 36,
            'is_active' => true,
        ]);

        $this->assertEquals('ended', $endedEvent->cameraStatus());
        $this->assertFalse($endedEvent->isCameraOpen());

        // Camera page shows closed gate
        $cameraResponse = $this->get('/e/past-wedding/camera');
        $cameraResponse->assertStatus(200);
        $cameraResponse->assertSee('Periode Foto Telah Selesai');
        $cameraResponse->assertSee('Buka Galeri Foto Bersama Tamu');

        // Capture attempt is blocked (403)
        $captureResponse = $this->postJson('/e/past-wedding/camera/capture', [
            'image' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        ]);
        $captureResponse->assertStatus(403);
        $captureResponse->assertJson(['success' => false]);

        // Gallery remains 100% accessible!
        $galleryResponse = $this->get('/e/past-wedding/gallery');
        $galleryResponse->assertStatus(200);
        $galleryResponse->assertSee('Public Event Gallery');
    }

    public function test_event_theme_presets_render_distinct_styles(): void
    {
        $oliveEvent = new Event(['theme_preset' => 'sacred_olive']);
        $oliveStyles = $oliveEvent->getThemeStyles();
        $this->assertEquals('bg-[#273529]', $oliveStyles['body_bg']);
        $this->assertStringContainsString('Sacred Church', $oliveStyles['tag']);

        $classicEvent = new Event(['theme_preset' => 'classic_film']);
        $classicStyles = $classicEvent->getThemeStyles();
        $this->assertEquals('bg-[#E5A93C]', $classicStyles['body_bg']);
        $this->assertStringContainsString('Vintage 35mm', $classicStyles['tag']);

        $ivoryEvent = new Event(['theme_preset' => 'ivory_gold']);
        $ivoryStyles = $ivoryEvent->getThemeStyles();
        $this->assertEquals('bg-[#FAF6F0]', $ivoryStyles['body_bg']);
    }

    public function test_guest_can_view_gallery(): void
    {
        $response = $this->get('/e/daniel-clara/gallery');
        $response->assertStatus(200);
        $response->assertSee('Public Event Gallery');
    }

    public function test_admin_can_access_qrcodes(): void
    {
        $response = $this->actingAs($this->adminUser)->get("/admin/events/{$this->event->id}/qrcodes");
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

        // Delete the event as authenticated admin
        $response = $this->actingAs($this->adminUser)->delete("/admin/events/{$this->event->id}");
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

        // Delete photo as authenticated admin
        $response = $this->actingAs($this->adminUser)->delete("/admin/photos/{$photo->id}");
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

        // Clear all photos as authenticated admin
        $response = $this->actingAs($this->adminUser)->post("/admin/events/{$this->event->id}/gallery/clear");
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

