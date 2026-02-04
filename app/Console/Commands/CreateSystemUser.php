<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateSystemUser extends Command
{
    protected $signature = 'ai:create-system-user';
    protected $description = 'Create a system user for AI-generated content';

    public function handle()
    {
        $this->info('Creating system user for AI content...');

        // Check if system user already exists
        $systemUser = User::where('email', 'system@ielts-ai.local')->first();
        
        if ($systemUser) {
            $this->info('✅ System user already exists: ' . $systemUser->name);
            return 0;
        }

        // Create system user
        $systemUser = User::create([
            'name' => 'AI System',
            'email' => 'system@ielts-ai.local',
            'password' => Hash::make('system-password-' . now()->timestamp),
            'role' => 'admin',
            'email_verified_at' => now()
        ]);

        $this->info('✅ System user created successfully!');
        $this->info('   Name: ' . $systemUser->name);
        $this->info('   Email: ' . $systemUser->email);
        $this->info('   ID: ' . $systemUser->id);
        $this->info('   Role: ' . $systemUser->role);

        $this->newLine();
        $this->info('This user will be used for AI-generated content creation.');
        
        return 0;
    }
}