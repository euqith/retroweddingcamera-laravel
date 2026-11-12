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

    public function test_admin_can_toggle_show_date_stamp_on_event_create_and_update(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create event with show_date_stamp enabled
        $createResponse = $this->post('/admin/events', [
            'title' => 'Event With Date Stamp',
            'slug' => 'event-with-date',
            'event_type' => 'wedding',
            'theme_preset' => 'ivory_gold',
            'filter_preset' => 'fuji_quicksnap',
            'max_shots_per_device' => 36,
            'show_date_stamp' => '1',
            'is_active' => '1',
        ]);
        $createResponse->assertRedirect();
        $eventA = Event::where('slug', 'event-with-date')->first();
        $this->assertNotNull($eventA);
        $this->assertTrue($eventA->show_date_stamp);

        // 2. Create event with show_date_stamp disabled
        $createResponseB = $this->post('/admin/events', [
            'title' => 'Event Without Date Stamp',
            'slug' => 'event-without-date',
            'event_type' => 'general',
            'theme_preset' => 'sacred_olive',
            'filter_preset' => 'warm_portra',
            'max_shots_per_device' => 24,
            // show_date_stamp omitted (unchecked checkbox)
            'is_active' => '1',
        ]);
        $createResponseB->assertRedirect();
        $eventB = Event::where('slug', 'event-without-date')->first();
        $this->assertNotNull($eventB);
        $this->assertFalse($eventB->show_date_stamp);

        // 3. Update event: toggle show_date_stamp from true to false
        $updateResponse = $this->put("/admin/events/{$eventA->id}", [
            'title' => 'Event With Date Stamp (Updated)',
            'slug' => 'event-with-date',
            'event_type' => 'wedding',
            'theme_preset' => 'ivory_gold',
            'filter_preset' => 'fuji_quicksnap',
            'max_shots_per_device' => 36,
            // show_date_stamp omitted -> false
        ]);
        $updateResponse->assertRedirect();
        $eventA->refresh();
        $this->assertFalse($eventA->show_date_stamp);

        // 4. Update event: toggle show_date_stamp back to true
        $updateResponse2 = $this->put("/admin/events/{$eventA->id}", [
            'title' => 'Event With Date Stamp (Re-enabled)',
            'slug' => 'event-with-date',
            'event_type' => 'wedding',
            'theme_preset' => 'ivory_gold',
            'filter_preset' => 'fuji_quicksnap',
            'max_shots_per_device' => 36,
            'show_date_stamp' => '1',
        ]);
        $updateResponse2->assertRedirect();
        $eventA->refresh();
        $this->assertTrue($eventA->show_date_stamp);
    }

    public function test_camera_receives_show_date_stamp_setting_from_event(): void
    {
        $this->event->update(['show_date_stamp' => false]);

        $response = $this->get("/e/{$this->event->slug}/camera");
        $response->assertStatus(200);
        $response->assertSee('showDateStamp: false', false);

        $this->event->update(['show_date_stamp' => true]);
        $response2 = $this->get("/e/{$this->event->slug}/camera");
        $response2->assertStatus(200);
        $response2->assertSee('showDateStamp: true', false);
    }

    public function test_guest_can_access_table_card_without_login(): void
    {
        // Must be 200 OK without any authentication
        $response = $this->get("/e/{$this->event->slug}/card");
        $response->assertStatus(200);
        $response->assertSee($this->event->title);
        $response->assertSee('BE OUR PHOTOGRAPHER');
        $response->assertSee('Buka Kamera &amp; Galeri Acara', false);

        // Also test the alias route
        $aliasResponse = $this->get("/e/{$this->event->slug}/qrcode");
        $aliasResponse->assertStatus(200);
    }

    public function test_guest_landing_page_contains_card_link(): void
    {
        $response = $this->get("/e/{$this->event->slug}");
        $response->assertStatus(200);
        $response->assertSee(route('event.card', $this->event->slug));
        $response->assertSee('Lihat &amp; Cetak Kartu Barcode Meja', false);
    }

    public function test_guest_can_capture_photo_with_summer_gold_preset(): void
    {
        // 1x1 transparent PNG as Data URI
        $dummyBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

        $response = $this->postJson("/e/{$this->event->slug}/camera/capture", [
            'image' => $dummyBase64,
            'filter_applied' => 'summer_gold',
        ], [
            'X-Device-Token' => 'DEVICE-SUMMER-GOLD',
        ]);

        $response->assertStatus(200);
        $photo = Photo::find($response->json('photo.id'));
        $this->assertNotNull($photo);
        $this->assertEquals('summer_gold', $photo->filter_applied);
    }

    public function test_admin_can_create_event_with_summer_gold_preset(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post('/admin/events', [
            'title' => 'Summer Wedding Event',
            'slug' => 'summer-wedding',
            'event_type' => 'wedding',
            'theme_preset' => 'sacred_olive',
            'filter_preset' => 'summer_gold',
            'max_shots_per_device' => 36,
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $event = Event::where('slug', 'summer-wedding')->first();
        $this->assertNotNull($event);
        $this->assertEquals('summer_gold', $event->filter_preset);
    }

    public function test_admin_can_create_event_with_google_drive_url(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post('/admin/events', [
            'title' => 'Drive Linked Event',
            'slug' => 'drive-linked-event',
            'event_type' => 'general',
            'theme_preset' => 'classic_film',
            'filter_preset' => 'lomography_color',
            'max_shots_per_device' => 24,
            'is_active' => '1',
            'google_drive_url' => 'https://drive.google.com/drive/folders/0B_xyz987FolderId',
        ]);

        $response->assertRedirect();
        $event = Event::where('slug', 'drive-linked-event')->first();
        $this->assertNotNull($event);
        $this->assertEquals('https://drive.google.com/drive/folders/0B_xyz987FolderId', $event->google_drive_url);
        $this->assertEquals('0B_xyz987FolderId', $event->google_drive_folder_id);
    }

    public function test_admin_can_register_google_drive_url(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post("/admin/events/{$this->event->id}/google-drive", [
            'google_drive_url' => 'https://drive.google.com/drive/folders/1aBcDeFgHiJkLmNoPqRsTuVwXyZ123456?usp=sharing',
        ]);

        $response->assertRedirect();
        $this->event->refresh();

        $this->assertEquals('https://drive.google.com/drive/folders/1aBcDeFgHiJkLmNoPqRsTuVwXyZ123456?usp=sharing', $this->event->google_drive_url);
        $this->assertEquals('1aBcDeFgHiJkLmNoPqRsTuVwXyZ123456', $this->event->google_drive_folder_id);
    }

    public function test_admin_can_disconnect_google_drive(): void
    {
        $this->actingAs($this->adminUser);

        $this->event->update([
            'google_drive_url' => 'https://drive.google.com/drive/folders/1aBcDeFgHiJkLmNoPqRsTuVwXyZ123456',
            'google_drive_folder_id' => '1aBcDeFgHiJkLmNoPqRsTuVwXyZ123456',
            'google_drive_sync_status' => 'completed',
        ]);

        $response = $this->post("/admin/events/{$this->event->id}/google-drive/disconnect");

        $response->assertRedirect();
        $this->event->refresh();

        $this->assertNull($this->event->google_drive_url);
        $this->assertNull($this->event->google_drive_folder_id);
        $this->assertEquals('idle', $this->event->google_drive_sync_status);
    }

    public function test_admin_can_download_photos_zip(): void
    {
        $this->actingAs($this->adminUser);

        $session = CameraSession::create([
            'event_id' => $this->event->id,
            'device_token' => 'DEVICE-ZIP-TEST',
            'shots_taken' => 1,
            'max_shots' => 36,
        ]);

        // Put a dummy photo in storage
        Storage::disk('public')->put("photos/{$this->event->slug}/test_shot.jpg", 'dummy-image-binary-data');

        Photo::create([
            'event_id' => $this->event->id,
            'camera_session_id' => $session->id,
            'shot_number' => 1,
            'file_path' => "photos/{$this->event->slug}/test_shot.jpg",
            'thumbnail_path' => "photos/{$this->event->slug}/test_shot_thumb.jpg",
            'filter_applied' => 'fuji_quicksnap',
            'taken_at' => now(),
        ]);

        $response = $this->get("/admin/events/{$this->event->id}/download-zip");

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-disposition') ?? '', '.zip'));
    }

    public function test_guest_gallery_displays_google_drive_button_when_configured(): void
    {
        $this->event->update([
            'google_drive_url' => 'https://drive.google.com/drive/folders/1aBcDeFgHiJkLmNoPqRsTuVwXyZ123456',
            'google_drive_folder_id' => '1aBcDeFgHiJkLmNoPqRsTuVwXyZ123456',
        ]);

        $response = $this->get("/e/{$this->event->slug}/gallery");

        $response->assertStatus(200);
        $response->assertSee('Buka Folder Google Drive Acara', false);
        $response->assertSee('https://drive.google.com/drive/folders/1aBcDeFgHiJkLmNoPqRsTuVwXyZ123456');
    }

    public function test_admin_can_save_and_update_event_with_empty_dates_and_optional_fields(): void
    {
        $this->actingAs($this->adminUser);

        // Store with empty date strings
        $response = $this->post('/admin/events', [
            'title' => 'Event Without Dates',
            'slug' => 'event-without-dates',
            'event_type' => 'general',
            'event_date' => '',
            'starts_at' => '',
            'ends_at' => '',
            'location' => '',
            'description' => '',
            'theme_preset' => 'classic_film',
            'filter_preset' => 'fuji_quicksnap',
            'max_shots_per_device' => 36,
            'is_active' => '1',
            'google_drive_url' => '',
        ]);

        $response->assertRedirect();
        $event = Event::where('slug', 'event-without-dates')->first();
        $this->assertNotNull($event);
        $this->assertNull($event->event_date);
        $this->assertNull($event->starts_at);
        $this->assertNull($event->ends_at);
        $this->assertNull($event->location);
        $this->assertNull($event->description);

        // Update with empty dates
        $updateResponse = $this->put("/admin/events/{$event->id}", [
            'title' => 'Event Without Dates Renamed',
            'slug' => 'event-without-dates',
            'event_type' => 'wedding',
            'event_date' => '',
            'starts_at' => '',
            'ends_at' => '',
            'location' => '',
            'description' => '',
            'theme_preset' => 'ivory_gold',
            'filter_preset' => 'lomography_color',
            'max_shots_per_device' => 20,
            'google_drive_url' => '',
        ]);

        $updateResponse->assertRedirect();
        $event->refresh();
        $this->assertEquals('Event Without Dates Renamed', $event->title);
        $this->assertNull($event->event_date);
        $this->assertNull($event->starts_at);
        $this->assertNull($event->ends_at);
    }

    public function test_google_drive_sync_does_not_duplicate_already_synced_photos(): void
    {
        $this->actingAs($this->adminUser);

        config(['services.google.drive_webhook_url' => 'https://script.google.com/macros/s/test/exec']);

        $this->event->update([
            'google_drive_url' => 'https://drive.google.com/drive/folders/1FakeFolderId',
            'google_drive_folder_id' => '1FakeFolderId',
        ]);

        $session = CameraSession::create([
            'event_id' => $this->event->id,
            'device_token' => 'DEVICE-SYNC-DEDUP',
            'shots_taken' => 2,
            'max_shots' => 36,
        ]);

        Storage::disk('public')->put("photos/{$this->event->slug}/shot1.jpg", 'photo-1-content');
        Storage::disk('public')->put("photos/{$this->event->slug}/shot2.jpg", 'photo-2-content');

        $photo1 = Photo::create([
            'event_id' => $this->event->id,
            'camera_session_id' => $session->id,
            'shot_number' => 1,
            'file_path' => "photos/{$this->event->slug}/shot1.jpg",
            'thumbnail_path' => "photos/{$this->event->slug}/shot1.jpg",
            'taken_at' => now(),
        ]);

        $photo2 = Photo::create([
            'event_id' => $this->event->id,
            'camera_session_id' => $session->id,
            'shot_number' => 2,
            'file_path' => "photos/{$this->event->slug}/shot2.jpg",
            'thumbnail_path' => "photos/{$this->event->slug}/shot2.jpg",
            'taken_at' => now(),
        ]);

        // First Sync: both photos are uploaded
        \Illuminate\Support\Facades\Http::fake([
            'https://script.google.com/macros/s/test/exec' => \Illuminate\Support\Facades\Http::response([
                'status' => 'success',
                'id' => 'gdrive_file_id_123',
                'name' => 'shot_1.jpg',
            ], 200),
        ]);

        $response1 = $this->post("/admin/events/{$this->event->id}/google-drive/sync");
        $response1->assertRedirect();

        $photo1->refresh();
        $photo2->refresh();
        $this->assertNotNull($photo1->google_drive_file_id);
        $this->assertNotNull($photo2->google_drive_file_id);
        $this->assertEquals(2, $this->event->fresh()->google_drive_synced_count);

        // Add 3rd photo
        Storage::disk('public')->put("photos/{$this->event->slug}/shot3.jpg", 'photo-3-content');
        $photo3 = Photo::create([
            'event_id' => $this->event->id,
            'camera_session_id' => $session->id,
            'shot_number' => 3,
            'file_path' => "photos/{$this->event->slug}/shot3.jpg",
            'thumbnail_path' => "photos/{$this->event->slug}/shot3.jpg",
            'taken_at' => now(),
        ]);

        // Second Sync: only 1 request should be sent (for photo 3)
        \Illuminate\Support\Facades\Http::fake([
            'https://script.google.com/macros/s/test/exec' => \Illuminate\Support\Facades\Http::response([
                'status' => 'success',
                'id' => 'gdrive_file_id_456',
                'name' => 'shot_3.jpg',
            ], 200),
        ]);

        $response2 = $this->post("/admin/events/{$this->event->id}/google-drive/sync");
        $response2->assertRedirect();

        // Verify only 1 HTTP request was made during the second sync!
        \Illuminate\Support\Facades\Http::assertSentCount(1);

        $photo3->refresh();
        $this->assertNotNull($photo3->google_drive_file_id);
        $this->assertEquals(3, $this->event->fresh()->google_drive_synced_count);
    }

    public function test_google_drive_sync_restores_accidentally_deleted_photos(): void
    {
        $this->actingAs($this->adminUser);

        config(['services.google.drive_webhook_url' => 'https://script.google.com/macros/s/test/exec']);

        $this->event->update([
            'google_drive_url' => 'https://drive.google.com/drive/folders/1FakeFolderId',
            'google_drive_folder_id' => '1FakeFolderId',
        ]);

        $session = CameraSession::create([
            'event_id' => $this->event->id,
            'device_token' => 'DEVICE-RESTORE-TEST',
            'shots_taken' => 2,
            'max_shots' => 36,
        ]);

        Storage::disk('public')->put("photos/{$this->event->slug}/restore1.jpg", 'photo-1-content');
        Storage::disk('public')->put("photos/{$this->event->slug}/restore2.jpg", 'photo-2-content');

        // Both photos were previously synced in DB
        $photo1 = Photo::create([
            'event_id' => $this->event->id,
            'camera_session_id' => $session->id,
            'shot_number' => 1,
            'file_path' => "photos/{$this->event->slug}/restore1.jpg",
            'thumbnail_path' => "photos/{$this->event->slug}/restore1.jpg",
            'google_drive_file_id' => 'old_drive_id_1',
            'taken_at' => now(),
        ]);

        $photo2 = Photo::create([
            'event_id' => $this->event->id,
            'camera_session_id' => $session->id,
            'shot_number' => 2,
            'file_path' => "photos/{$this->event->slug}/restore2.jpg",
            'thumbnail_path' => "photos/{$this->event->slug}/restore2.jpg",
            'google_drive_file_id' => 'existing_drive_id_2',
            'taken_at' => now(),
        ]);

        // Mock Webhook response:
        // Photo 1 was deleted in Drive, so Apps Script recreated it (already_exists = false, new id)
        // Photo 2 still exists in Drive, so Apps Script returned already_exists = true
        \Illuminate\Support\Facades\Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $data = $request->data();
            if (str_contains($data['filename'] ?? '', 'shot_1_')) {
                return \Illuminate\Support\Facades\Http::response([
                    'status' => 'success',
                    'id' => 'restored_drive_id_1',
                    'already_exists' => false,
                ], 200);
            }

            return \Illuminate\Support\Facades\Http::response([
                'status' => 'success',
                'id' => 'existing_drive_id_2',
                'already_exists' => true,
            ], 200);
        });

        // Trigger sync when no pending photos in DB: should automatically verify all and restore photo 1
        $response = $this->post("/admin/events/{$this->event->id}/google-drive/sync");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertTrue(str_contains(session('success'), 'memulihkan 1 foto'));

        $photo1->refresh();
        $photo2->refresh();

        $this->assertEquals('restored_drive_id_1', $photo1->google_drive_file_id);
        $this->assertEquals('existing_drive_id_2', $photo2->google_drive_file_id);
        $this->assertEquals(2, $this->event->fresh()->google_drive_synced_count);
    }

    public function test_event_google_drive_frequency_helpers(): void
    {
        $this->event->update([
            'google_drive_url' => 'https://drive.google.com/drive/folders/test-folder-123',
            'google_drive_folder_id' => 'test-folder-123',
            'google_drive_sync_frequency' => 'daily',
            'google_drive_last_scheduled_sync_at' => now()->subHours(25),
        ]);

        $this->assertTrue($this->event->isGoogleDriveSyncDue());
        $this->assertEquals(24, $this->event->googleDriveSyncFrequencyHours());
        $this->assertEquals('1 Hari Sekali (Setiap Hari)', $this->event->googleDriveSyncFrequencyLabel());

        // When recently synced (e.g. 2 hours ago)
        $this->event->update([
            'google_drive_last_scheduled_sync_at' => now()->subHours(2),
        ]);
        $this->assertFalse($this->event->isGoogleDriveSyncDue());

        // When manual
        $this->event->update([
            'google_drive_sync_frequency' => 'manual',
            'google_drive_last_scheduled_sync_at' => now()->subDays(5),
        ]);
        $this->assertFalse($this->event->isGoogleDriveSyncDue());
        $this->assertNull($this->event->nextGoogleDriveSyncDueAt());
    }

    public function test_scheduled_sync_command_syncs_due_events(): void
    {
        config(['services.google.drive_webhook_url' => 'https://script.google.com/macros/s/test/exec']);

        $this->event->update([
            'google_drive_url' => 'https://drive.google.com/drive/folders/1DueFolder',
            'google_drive_folder_id' => '1DueFolder',
            'google_drive_sync_frequency' => 'daily',
            'google_drive_last_scheduled_sync_at' => now()->subHours(30),
        ]);

        $session = CameraSession::create([
            'event_id' => $this->event->id,
            'device_token' => 'DEVICE-CRON-TEST',
            'shots_taken' => 1,
            'max_shots' => 36,
        ]);

        Storage::disk('public')->put("photos/{$this->event->slug}/cron1.jpg", 'cron-photo-content');

        $photo = Photo::create([
            'event_id' => $this->event->id,
            'camera_session_id' => $session->id,
            'shot_number' => 1,
            'file_path' => "photos/{$this->event->slug}/cron1.jpg",
            'thumbnail_path' => "photos/{$this->event->slug}/cron1.jpg",
            'taken_at' => now(),
        ]);

        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response([
                'status' => 'success',
                'id' => 'cron_drive_file_id',
                'already_exists' => false,
            ], 200),
        ]);

        $exitCode = \Illuminate\Support\Facades\Artisan::call('gdrive:sync-scheduled');
        $this->assertEquals(0, $exitCode);

        $this->event->refresh();
        $photo->refresh();

        $this->assertNotNull($this->event->google_drive_last_scheduled_sync_at);
        $this->assertTrue($this->event->google_drive_last_scheduled_sync_at->isToday());
        $this->assertEquals('cron_drive_file_id', $photo->google_drive_file_id);
    }

    public function test_scheduled_sync_command_skips_events_not_due(): void
    {
        config(['services.google.drive_webhook_url' => 'https://script.google.com/macros/s/test/exec']);

        $this->event->update([
            'google_drive_url' => 'https://drive.google.com/drive/folders/1NotDueFolder',
            'google_drive_folder_id' => '1NotDueFolder',
            'google_drive_sync_frequency' => 'daily',
            'google_drive_last_scheduled_sync_at' => now()->subHours(2),
        ]);

        \Illuminate\Support\Facades\Http::fake();

        $exitCode = \Illuminate\Support\Facades\Artisan::call('gdrive:sync-scheduled');
        $this->assertEquals(0, $exitCode);

        \Illuminate\Support\Facades\Http::assertNothingSent();
    }
}

