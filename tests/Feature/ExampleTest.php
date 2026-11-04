<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Retro Wedding Camera');
        $response->assertSee('Event Organizer Portal');
    }

    public function test_login_authenticates_and_redirects_to_admin(): void
    {
        $user = \App\Models\User::where('name', 'adminretro')->first();

        // Test login using username
        $response = $this->post('/login', [
            'login' => 'adminretro',
            'password' => 'Adm1n456',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_quick_login_is_disabled_in_production(): void
    {
        $response = $this->post('/login/quick');
        $response->assertStatus(404);
    }

    public function test_login_rate_limiting_protects_against_brute_force(): void
    {
        // 5 wrong attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'login' => 'adminretro',
                'password' => 'WrongPassword!',
            ]);
        }

        // 6th attempt should be blocked by RateLimiter
        $response = $this->post('/login', [
            'login' => 'adminretro',
            'password' => 'WrongPassword!',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('login'));
    }
}
