<?php

namespace Database\Factories;

use App\Models\WritingTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WritingTaskFactory extends Factory
{
    protected $model = WritingTask::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(4),
            'task_type' => $this->faker->randomElement(['task1', 'task2']),
            'prompt' => $this->faker->paragraph(),
            'instructions' => $this->faker->sentence(),
            'word_limit' => $this->faker->numberBetween(150, 300),
            'time_limit' => $this->faker->numberBetween(20, 60),
            'created_by' => User::factory()->create(['role' => 'admin'])->id
        ];
    }
}
