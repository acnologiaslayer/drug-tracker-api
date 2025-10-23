<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function ensureSqliteDriver(): void
    {
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite driver not available.');
        }
    }

    public function test_it_can_login_with_valid_credentials(): void
    {
        $this->ensureSqliteDriver();

        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Login successful.',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['access_token', 'token_type'],
            ]);
    }

    public function test_it_rejects_invalid_credentials(): void
    {
        $this->ensureSqliteDriver();

        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('correctpassword'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials.',
            ]);
    }
}
