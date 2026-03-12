<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expenses>
 */
class ExpensesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory()->create()->first()->id, // Asignar a un usuario existente o crear uno nuevo
            'category_id' => \App\Models\Category::factory()->create()->first()->id,
            'amount' => fake()->randomFloat(2, 1, 1000),
            'merchant' => fake()->company(),
            'expense_date' => fake()->date(),
            'notes' => fake()->text(),
        ];
    }
}
