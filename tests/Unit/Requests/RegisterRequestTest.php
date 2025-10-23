<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RegisterRequestTest extends TestCase
{
    use RefreshDatabase;

    private function ensureSqliteDriver(): void
    {
        if (! extension_loaded('sqlite3')) {
            $this->markTestSkipped('SQLite driver not available.');
        }
    }

    public function test_it_requires_name_email_and_password(): void
    {
        $this->ensureSqliteDriver();

    $request = new RegisterRequest();

    $validator = Validator::make([], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertEqualsCanonicalizing(
            ['name', 'email', 'password'],
            array_keys($validator->errors()->messages())
        );
    }

    public function test_it_validates_email_format(): void
    {
        $this->ensureSqliteDriver();

        $request = new RegisterRequest();

        $validator = Validator::make([
            'name' => 'Jane Doe',
            'email' => 'invalid-email',
            'password' => 'Password123!',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->messages());
    }

    public function test_it_enforces_minimum_password_length(): void
    {
        $this->ensureSqliteDriver();

        $request = new RegisterRequest();

        $validator = Validator::make([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'short',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->messages());
    }

    public function test_it_ensures_email_uniqueness(): void
    {
        $this->ensureSqliteDriver();

        User::factory()->create(['email' => 'duplicate@example.com']);

        $request = new RegisterRequest();

        $validator = Validator::make([
            'name' => 'Jane Doe',
            'email' => 'duplicate@example.com',
            'password' => 'Password123!',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->messages());
    }
}
