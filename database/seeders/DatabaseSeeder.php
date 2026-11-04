<?php

namespace Database\Seeders;

use App\Models\CameraSession;
use App\Models\Event;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@retrocamera.test'],
            [
                'name' => 'Event Admin',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Sample Wedding Event
        $wedding = Event::firstOrCreate(
            ['slug' => 'daniel-clara'],
            [
                'user_id' => $admin->id,
                'title' => 'The Wedding of Daniel & Clara',
                'event_type' => 'wedding',
                'event_date' => now()->format('Y-m-d'),
                'location' => 'St. Mary Cathedral & Grand Ballroom',
                'description' => 'Abadikan setiap momen hangat dan tak terlupakan menggunakan kamera disposable digital kami. Setiap tamu mendapatkan 36 jepretan film analog!',
                'theme_preset' => 'ivory_gold',
                'filter_preset' => 'fuji_quicksnap',
                'max_shots_per_device' => 36,
                'is_active' => true,
                'is_gallery_public' => true,
            ]
        );

        // 3. Sample Church Event
        $church = Event::firstOrCreate(
            ['slug' => 'youth-christmas-celebration'],
            [
                'user_id' => $admin->id,
                'title' => 'Youth Christmas & New Year Service',
                'event_type' => 'church',
                'event_date' => now()->addDays(7)->format('Y-m-d'),
                'location' => 'Gereja Kristen Grace Hall',
                'description' => 'Ibadah dan perayaan natal bersama pemuda-pemudi gereja.',
                'theme_preset' => 'sacred_olive',
                'filter_preset' => 'kodak_funsaver',
                'max_shots_per_device' => 36,
                'is_active' => true,
                'is_gallery_public' => true,
            ]
        );

        // 4. Sample Session & Photos from attachment
        $session = CameraSession::firstOrCreate(
            ['event_id' => $wedding->id, 'device_token' => 'DEMO-DEVICE-1'],
            [
                'guest_name' => 'Bestman & Bridesmaids',
                'shots_taken' => 6,
                'max_shots' => 36,
                'reset_count' => 0,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)',
                'last_shot_at' => now(),
            ]
        );

        $attachmentDir = base_path('attachment');
        if (File::isDirectory($attachmentDir)) {
            $files = File::files($attachmentDir);
            $shotNumber = 1;

            foreach ($files as $file) {
                if (in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'])) {
                    $filename = 'photo_' . $shotNumber . '_' . Str::random(8) . '.' . $file->getExtension();
                    $destinationPath = 'photos/' . $wedding->slug . '/' . $filename;

                    Storage::disk('public')->put($destinationPath, File::get($file->getRealPath()));

                    Photo::firstOrCreate(
                        ['event_id' => $wedding->id, 'camera_session_id' => $session->id, 'shot_number' => $shotNumber],
                        [
                            'file_path' => $destinationPath,
                            'thumbnail_path' => $destinationPath,
                            'camera_facing' => $shotNumber % 2 === 0 ? 'user' : 'environment',
                            'flash_enabled' => $shotNumber % 2 !== 0,
                            'filter_applied' => 'fuji_quicksnap',
                            'caption' => 'Candid moment #' . $shotNumber,
                            'is_visible' => true,
                            'taken_at' => now()->subMinutes(10 * (7 - $shotNumber)),
                        ]
                    );

                    $shotNumber++;
                }
            }
        }
    }
}
