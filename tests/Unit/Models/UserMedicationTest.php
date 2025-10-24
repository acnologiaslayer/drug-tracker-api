<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\UserMedication;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class UserMedicationTest extends TestCase
{
    public function test_array_attributes_are_cast_to_arrays(): void
    {
        $medication = new UserMedication([
            'base_names' => ['Aspirin'],
            'dose_form_group_names' => ['Tablet'],
        ]);

        $this->assertSame(['Aspirin'], $medication->base_names);
        $this->assertSame(['Tablet'], $medication->dose_form_group_names);
    }

    public function test_user_relationship_returns_belongs_to(): void
    {
        $medication = new UserMedication();

        $relationship = $medication->user();

        $this->assertInstanceOf(BelongsTo::class, $relationship);
        $this->assertInstanceOf(User::class, $relationship->getRelated());
    }
}
