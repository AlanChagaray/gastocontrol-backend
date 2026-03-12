<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Food', 'Transport', 'Entertainment', 'Shopping', 'Health', 'Other']),
            'icon' => fake()->randomElement(['food', 'transport', 'entertainment', 'shopping', 'health', 'other']),
            'color' => fake()->hexColor(),
            'sort_order' => fake()->numberBetween(0, 10),
            'user_id' => \App\Models\User::factory()->create()->first()->id, // Asignar a un usuario existente o crear uno nuevo
        ];
    }
}
