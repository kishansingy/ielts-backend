<?php

namespace Database\Factories;

use App\Models\ReadingPassage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReadingPassageFactory extends Factory
{
    protected $model = ReadingPassage::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(4),
            'content' => $this->faker->paragraphs(3, true),
            'difficulty_level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'time_limit' => $this->faker->numberBetween(15, 30),
            'created_by' => User::factory()->create(['role' => 'admin'])->id
        ];
    }
}
