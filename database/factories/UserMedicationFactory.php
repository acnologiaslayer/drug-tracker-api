<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserMedication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserMedication>
 */
class UserMedicationFactory extends Factory
{
    protected $model = UserMedication::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'rxcui' => (string) $this->faker->randomNumber(6, true),
            'drug_name' => $this->faker->sentence(3),
            'base_names' => [$this->faker->word()],
            'dose_form_group_names' => [$this->faker->word()],
        ];
    }
}
