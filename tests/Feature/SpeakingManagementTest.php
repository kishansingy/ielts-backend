<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SpeakingPrompt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class SpeakingManagementTest extends TestCase
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
    public function admin_can_list_speaking_prompts()
    {
        SpeakingPrompt::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/speaking-prompts');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'difficulty_level', 'prompt_text']
                ]
            ]);
    }

    /** @test */
    public function admin_can_create_speaking_prompt()
    {
        $data = [
            'title' => 'Speaking Task 1',
            'prompt_text' => 'Describe your favorite hobby.',
            'preparation_time' => 60,
            'response_time' => 120,
            'difficulty_level' => 'intermediate',
            'band_level' => 'band7'
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/speaking-prompts', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Speaking Task 1']);

        $this->assertDatabaseHas('speaking_prompts', [
            'title' => 'Speaking Task 1'
        ]);
    }

    /** @test */
    public function admin_can_update_speaking_prompt()
    {
        $prompt = SpeakingPrompt::factory()->create([
            'title' => 'Original Prompt'
        ]);

        $data = [
            'title' => 'Updated Prompt',
            'prompt_text' => 'Updated prompt text',
            'preparation_time' => 90,
            'response_time' => 180,
            'difficulty_level' => 'advanced',
            'band_level' => 'band8'
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/speaking-prompts/{$prompt->id}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment(['title' => 'Updated Prompt']);

        $this->assertDatabaseHas('speaking_prompts', [
            'id' => $prompt->id,
            'title' => 'Updated Prompt'
        ]);
    }

    /** @test */
    public function admin_can_delete_speaking_prompt()
    {
        $prompt = SpeakingPrompt::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/speaking-prompts/{$prompt->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('speaking_prompts', [
            'id' => $prompt->id
        ]);
    }

    /** @test */
    public function student_cannot_access_admin_speaking_endpoints()
    {
        $response = $this->actingAs($this->student, 'api')
            ->getJson('/api/admin/speaking-prompts');

        $response->assertStatus(403);
    }
}
