<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\ReadingPassage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class ReadingManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $student;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@test.com'
        ]);
        
        // Create student user
        $this->student = User::factory()->create([
            'role' => 'student',
            'email' => 'student@test.com'
        ]);
    }

    /** @test */
    public function admin_can_list_reading_passages()
    {
        ReadingPassage::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/reading-passages');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'difficulty_level', 'content']
                ]
            ]);
    }

    /** @test */
    public function admin_can_create_reading_passage()
    {
        $data = [
            'title' => 'Test Passage',
            'content' => 'This is a test passage for reading comprehension.',
            'difficulty_level' => 'intermediate',
            'time_limit' => 20,
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
            ->postJson('/api/admin/reading-passages', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Test Passage']);

        $this->assertDatabaseHas('reading_passages', [
            'title' => 'Test Passage',
            'difficulty_level' => 'intermediate'
        ]);
    }

    /** @test */
    public function admin_can_update_reading_passage()
    {
        $passage = ReadingPassage::factory()->create([
            'title' => 'Original Title'
        ]);

        $data = [
            'title' => 'Updated Title',
            'content' => 'Updated passage text',
            'difficulty_level' => 'advanced',
            'time_limit' => 25,
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
            ->putJson("/api/admin/reading-passages/{$passage->id}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated Title']);

        $this->assertDatabaseHas('reading_passages', [
            'id' => $passage->id,
            'title' => 'Updated Title',
            'difficulty_level' => 'advanced'
        ]);
    }

    /** @test */
    public function admin_can_delete_reading_passage()
    {
        $passage = ReadingPassage::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/reading-passages/{$passage->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('reading_passages', [
            'id' => $passage->id
        ]);
    }

    /** @test */
    public function student_cannot_access_admin_reading_endpoints()
    {
        $response = $this->actingAs($this->student, 'api')
            ->getJson('/api/admin/reading-passages');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_access_admin_reading_endpoints()
    {
        $response = $this->getJson('/api/admin/reading-passages');

        $response->assertStatus(401);
    }
}
