<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\UserMedication;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_casts_include_expected_attributes(): void
    {
        $user = new User();

        $casts = $user->getCasts();

        $this->assertSame('datetime', $casts['email_verified_at'] ?? null);
        $this->assertSame('hashed', $casts['password'] ?? null);
    }

    public function test_medications_relationship_returns_has_many(): void
    {
        $user = new User();

        $relationship = $user->medications();

        $this->assertInstanceOf(HasMany::class, $relationship);
        $this->assertInstanceOf(UserMedication::class, $relationship->getRelated());
    }
}
