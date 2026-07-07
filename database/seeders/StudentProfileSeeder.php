<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;

class StudentProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get all users with role 'student' that don't have a student profile yet
        $studentUsers = User::where('role', 'student')
            ->whereDoesntHave('student')
            ->get();

        foreach ($studentUsers as $user) {
            Student::create([
                'user_id' => $user->id,
                'band_level' => $user->band_level ?? 'band6',
                'school_name' => $user->school_name ?? 'Default School',
                'is_active' => $user->is_active ?? true,
            ]);
        }

        $this->command->info('Created student profiles for ' . $studentUsers->count() . ' users');
    }
}
