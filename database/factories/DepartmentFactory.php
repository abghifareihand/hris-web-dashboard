<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->word() . ' Department';
        return [
            'name' => ucwords($name),
            'code' => strtoupper(substr($name, 0, 3)) . $this->faker->unique()->numberBetween(10, 99),
            'description' => $this->faker->sentence(),
            'is_active' => $this->faker->boolean(90), // 90% chance to be active
        ];
    }
}
