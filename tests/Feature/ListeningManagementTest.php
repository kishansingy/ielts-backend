<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ListeningExercise;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ListeningManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $student;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->student = User::factory()->create(['role' => 'student']);
    }

    /** @test */
    public function admin_can_list_listening_exercises()
    {
        ListeningExercise::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/listening-exercises');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'difficulty_level']
                ]
            ]);
    }

    /** @test */
    public function admin_can_create_listening_exercise()
    {
        $audioFile = UploadedFile::fake()->create('audio.mp3', 1000, 'audio/mpeg');

        $data = [
            'title' => 'Listening Exercise 1',
            'audio_file' => $audioFile,
            'transcript' => 'This is the audio transcript',
            'duration' => 180,
            'difficulty_level' => 'intermediate',
            'questions' => [
                [
                    'question_text' => 'What is the main topic?',
                    'question_type' => 'multiple_choice',
                    'options' => ['A', 'B', 'C', 'D'],
                    'correct_answer' => 'A',
                    'points' => 1
                ]
            ]
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/listening-exercises', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Listening Exercise 1']);

        $this->assertDatabaseHas('listening_exercises', [
            'title' => 'Listening Exercise 1',
            'difficulty_level' => 'intermediate'
        ]);
    }

    /** @test */
    public function admin_can_update_listening_exercise()
    {
        $exercise = ListeningExercise::factory()->create([
            'title' => 'Original Exercise'
        ]);

        $data = [
            'title' => 'Updated Exercise',
            'difficulty_level' => 'advanced',
            'duration' => 240,
            'transcript' => 'Updated transcript',
            'questions' => [
                [
                    'question_text' => 'Updated question?',
                    'question_type' => 'multiple_choice',
                    'options' => ['A', 'B', 'C', 'D'],
                    'correct_answer' => 'B',
                    'points' => 1
                ]
            ]
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/listening-exercises/{$exercise->id}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated Exercise']);

        $this->assertDatabaseHas('listening_exercises', [
            'id' => $exercise->id,
            'title' => 'Updated Exercise'
        ]);
    }

    /** @test */
    public function admin_can_delete_listening_exercise()
    {
        $exercise = ListeningExercise::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/listening-exercises/{$exercise->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('listening_exercises', [
            'id' => $exercise->id
        ]);
    }

    /** @test */
    public function student_cannot_access_admin_listening_endpoints()
    {
        $response = $this->actingAs($this->student, 'api')
            ->getJson('/api/admin/listening-exercises');

        $response->assertStatus(403);
    }
}
