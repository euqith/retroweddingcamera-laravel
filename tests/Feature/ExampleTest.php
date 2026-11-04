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
}
