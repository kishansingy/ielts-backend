<?php

namespace Database\Factories;

use App\Models\ListeningExercise;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ListeningExerciseFactory extends Factory
{
    protected $model = ListeningExercise::class;

    public function definition()
    {
        return [
            'title' => $this->faker->sentence(4),
            'audio_file_path' => 'audio/test-audio.mp3',
            'transcript' => $this->faker->paragraph(),
            'duration' => $this->faker->numberBetween(120, 300),
            'difficulty_level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'created_by' => User::factory()->create(['role' => 'admin'])->id
        ];
    }
}
