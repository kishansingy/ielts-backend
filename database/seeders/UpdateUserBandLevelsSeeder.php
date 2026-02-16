<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UpdateUserBandLevelsSeeder extends Seeder
{
    /**
     * Assign band levels to existing users
     */
    public function run(): void
    {
        $this->command->info('Updating user band levels...');
        
        // Update admin to have access to all levels (keep NULL or set to band6)
        User::where('role', 'admin')->update(['band_level' => 'band6']);
        
        // Get all students
        $students = User::where('role', 'student')->whereNull('band_level')->get();
        
        if ($students->isEmpty()) {
            $this->command->info('No students need band level updates.');
            return;
        }
        
        // Distribute students across band levels
        $bandLevels = ['band6', 'band7', 'band8', 'band9'];
        $totalStudents = $students->count();
        $studentsPerBand = ceil($totalStudents / count($bandLevels));
        
        $index = 0;
        foreach ($students as $student) {
            $bandIndex = floor($index / $studentsPerBand);
            $bandLevel = $bandLevels[min($bandIndex, count($bandLevels) - 1)];
            
            $student->update(['band_level' => $bandLevel]);
            $this->command->info("Updated {$student->email} to {$bandLevel}");
            
            $index++;
        }
        
        $this->command->info('User band levels updated successfully!');
        $this->command->info('');
        $this->command->info('Band Level Distribution:');
        foreach ($bandLevels as $level) {
            $count = User::where('band_level', $level)->count();
            $this->command->info("  {$level}: {$count} users");
        }
    }
}
