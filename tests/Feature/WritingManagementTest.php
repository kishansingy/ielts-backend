<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\WritingTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class WritingManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $student;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->student = User::factory()->create(['role' => 'student']);
    }

    /** @test */
    public function admin_can_list_writing_tasks()
    {
        WritingTask::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/writing-tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'task_type', 'prompt']
                ]
            ]);
    }

    /** @test */
    public function admin_can_create_writing_task()
    {
        $data = [
            'title' => 'Essay Writing Task',
            'task_type' => 'task2',
            'prompt' => 'Write an essay about climate change.',
            'instructions' => 'Write at least 250 words',
            'word_limit' => 250,
            'time_limit' => 40,
            'band_level' => 'band7'
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/writing-tasks', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Essay Writing Task']);

        $this->assertDatabaseHas('writing_tasks', [
            'title' => 'Essay Writing Task',
            'task_type' => 'task2'
        ]);
    }

    /** @test */
    public function admin_can_update_writing_task()
    {
        $task = WritingTask::factory()->create([
            'title' => 'Original Task'
        ]);

        $data = [
            'title' => 'Updated Task',
            'task_type' => 'task1',
            'prompt' => 'Updated prompt',
            'instructions' => 'Updated instructions',
            'word_limit' => 300,
            'time_limit' => 45,
            'band_level' => 'band8'
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/writing-tasks/{$task->id}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated Task']);

        $this->assertDatabaseHas('writing_tasks', [
            'id' => $task->id,
            'title' => 'Updated Task'
        ]);
    }

    /** @test */
    public function admin_can_delete_writing_task()
    {
        $task = WritingTask::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/writing-tasks/{$task->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('writing_tasks', [
            'id' => $task->id
        ]);
    }

    /** @test */
    public function student_cannot_access_admin_writing_endpoints()
    {
        $response = $this->actingAs($this->student, 'api')
            ->getJson('/api/admin/writing-tasks');

        $response->assertStatus(403);
    }
}
