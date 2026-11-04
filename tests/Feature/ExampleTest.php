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
        $event = Event::create([
            'title' => 'Sample Wedding Event',
            'slug' => 'sample-wedding',
            'event_type' => 'wedding',
            'max_shots_per_device' => 36,
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertRedirect('/e/sample-wedding');
    }
}
