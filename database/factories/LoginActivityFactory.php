<?php

namespace Database\Factories;

use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoginActivityFactory extends Factory
{
    protected $model = LoginActivity::class;

    public function definition()
    {
        $status = $this->faker->randomElement(['success', 'failed', 'logout']);
        
        return [
            'user_id' => User::factory(),
            'login_type' => $this->faker->randomElement(['email', 'mobile']),
            'email' => $this->faker->email,
            'mobile' => $this->faker->numerify('##########'),
            'status' => $status,
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'device_type' => $this->faker->randomElement(['web', 'mobile']),
            'failure_reason' => $status === 'failed' ? $this->faker->sentence : null,
            'logged_in_at' => $status === 'success' ? now() : null,
            'logged_out_at' => $status === 'logout' ? now() : null,
        ];
    }

    public function success()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'success',
                'logged_in_at' => now(),
                'failure_reason' => null,
            ];
        });
    }

    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
                'failure_reason' => 'Invalid credentials',
                'logged_in_at' => null,
            ];
        });
    }

    public function logout()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'logout',
                'logged_out_at' => now(),
            ];
        });
    }
}
