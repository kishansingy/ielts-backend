<?php

namespace Database\Factories;

use App\Models\SpeakingPrompt;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SpeakingPromptFactory extends Factory
{
    protected $model = SpeakingPrompt::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(4),
            'prompt_text' => $this->faker->paragraph(),
            'preparation_time' => $this->faker->numberBetween(30, 60),
            'response_time' => $this->faker->numberBetween(60, 180),
            'difficulty_level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'created_by' => User::factory()->create(['role' => 'admin'])->id
        ];
    }
}
