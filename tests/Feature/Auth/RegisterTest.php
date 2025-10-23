<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function ensureSqliteDriver(): void
    {
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite driver not available.');
        }
    }

    public function test_it_can_register_a_new_user(): void
    {
        $this->ensureSqliteDriver();

        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass123!',
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'access_token',
                    'token_type',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    public function test_it_prevents_duplicate_email_registration(): void
    {
        $this->ensureSqliteDriver();

        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'existing@example.com',
            'password' => 'AnotherPass123!',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
