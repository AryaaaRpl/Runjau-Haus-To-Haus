<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@runningevent.test',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_failed_login_displays_error(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'wrong@runningevent.test',
            'password' => 'wrongpass',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_attempts_are_throttled_after_too_many_failures(): void
    {
        RateLimiter::clear(RateLimiter::class);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => 'spammer@runningevent.test',
                'password' => 'wrongpass',
            ]);
        }

        $response = $this->from('/login')->post('/login', [
            'email' => 'spammer@runningevent.test',
            'password' => 'wrongpass',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $errors = session('errors')->get('email');
        $this->assertNotEmpty($errors);
        $this->assertTrue(str_contains(strtolower($errors[0]), 'too many') || str_contains(strtolower($errors[0]), 'terlalu banyak'));
    }
}
