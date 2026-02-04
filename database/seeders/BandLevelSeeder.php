<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\MockTest;
use App\Models\Module;
use Illuminate\Support\Facades\Hash;

class BandLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create sample students with different band levels
        $students = [
            [
                'name' => 'Alice Johnson',
                'email' => 'alice@school1.edu',
                'password' => Hash::make('password'),
                'role' => 'student',
                'band_level' => 'band6',
                'school_name' => 'Cambridge International School',
                'is_active' => true
            ],
            [
                'name' => 'Bob Smith',
                'email' => 'bob@school1.edu',
                'password' => Hash::make('password'),
                'role' => 'student',
                'band_level' => 'band7',
                'school_name' => 'Cambridge International School',
                'is_active' => true
            ],
            [
                'name' => 'Carol Davis',
                'email' => 'carol@school2.edu',
                'password' => Hash::make('password'),
                'role' => 'student',
                'band_level' => 'band8',
                'school_name' => 'Oxford Academy',
                'is_active' => true
            ],
            [
                'name' => 'David Wilson',
                'email' => 'david@school2.edu',
                'password' => Hash::make('password'),
                'role' => 'student',
                'band_level' => 'band9',
                'school_name' => 'Oxford Academy',
                'is_active' => true
            ],
            [
                'name' => 'Emma Brown',
                'email' => 'emma@school3.edu',
                'password' => Hash::make('password'),
                'role' => 'student',
                'band_level' => 'band6',
                'school_name' => 'International Language Center',
                'is_active' => false // Inactive student
            ]
        ];

        foreach ($students as $studentData) {
            User::create($studentData);
        }

        // Update existing modules with band levels
        $modules = Module::all();
        $bandLevels = ['band6', 'band7', 'band8', 'band9'];
        
        foreach ($modules as $module) {
            $module->update([
                'band_level' => $bandLevels[array_rand($bandLevels)]
            ]);
        }

        // Update existing mock tests with band levels
        $mockTests = MockTest::all();
        
        foreach ($mockTests as $mockTest) {
            $mockTest->update([
                'band_level' => $bandLevels[array_rand($bandLevels)]
            ]);
        }

        $this->command->info('Band level seeder completed successfully!');
        $this->command->info('Created 5 sample students with different band levels');
        $this->command->info('Updated existing modules and mock tests with band levels');
    }
}