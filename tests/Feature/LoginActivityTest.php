<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LoginActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginActivityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_logs_successful_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login_type' => 'email',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);
        
        // Check login activity was recorded
        $this->assertDatabaseHas('login_activities', [
            'user_id' => $user->id,
            'email' => 'test@example.com',
            'status' => 'success',
            'login_type' => 'email'
        ]);
    }

    /** @test */
    public function it_logs_failed_login()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login_type' => 'email',
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(422);
        
        // Check failed login was recorded
        $this->assertDatabaseHas('login_activities', [
            'email' => 'test@example.com',
            'status' => 'failed'
        ]);
    }

    /** @test */
    public function it_logs_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->postJson('/api/auth/logout', [], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200);
        
        // Check logout was recorded
        $this->assertDatabaseHas('login_activities', [
            'user_id' => $user->id,
            'status' => 'logout'
        ]);
    }

    /** @test */
    public function it_returns_login_history()
    {
        $user = User::factory()->create();
        
        // Create some login activities
        LoginActivity::factory()->count(5)->create([
            'user_id' => $user->id
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->getJson('/api/auth/login-history', [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'status',
                        'ip_address',
                        'device_type',
                        'created_at'
                    ]
                ]
            ]);
    }

    /** @test */
    public function token_includes_expiration()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/auth/login', [
            'login_type' => 'email',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'token',
                'expires_at'
            ]);
        
        // Verify expires_at is in the future
        $expiresAt = new \DateTime($response->json('expires_at'));
        $this->assertGreaterThan(new \DateTime(), $expiresAt);
    }
}
