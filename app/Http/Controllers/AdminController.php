<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    /**
     * Get all students for admin management
     */
    public function getStudents(Request $request)
    {
        try {
            \Log::info('AdminController@getStudents called with params: ' . json_encode($request->all()));
            
            $query = Student::with('user');

            // Filter by band level if provided
            if ($request->has('band_level') && $request->band_level) {
                $query->where('band_level', $request->band_level);
            }

            // Filter by school if provided
            if ($request->has('school_name') && $request->school_name) {
                $query->where('school_name', $request->school_name);
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Search by name or email
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $students = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));
            
            \Log::info('Students query result: ' . $students->count() . ' students found');

            return response()->json([
                'success' => true,
                'students' => $students
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getStudents: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'students' => ['data' => [], 'current_page' => 1, 'last_page' => 1, 'total' => 0, 'from' => 0, 'to' => 0]
            ], 500);
        }
    }

    /**
     * Create a new student
     */
    public function createStudent(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'band_level' => 'required|in:band6,band7,band8,band9',
            'school_name' => 'nullable|string|max:255',
            'class_name' => 'nullable|string|max:255',
            'grade_level' => 'nullable|string|max:255',
            'mobile_number' => 'nullable|string|max:20',
            'parent_name' => 'nullable|string|max:255',
            'parent_email' => 'nullable|email|max:255',
            'parent_mobile' => 'nullable|string|max:20',
            'is_active' => 'boolean'
        ]);

        DB::beginTransaction();
        try {
            // Create user account
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'student',
                'is_active' => $request->get('is_active', true)
            ]);

            // Create student profile
            $student = Student::create([
                'user_id' => $user->id,
                'band_level' => $request->band_level,
                'school_name' => $request->school_name,
                'class_name' => $request->class_name,
                'grade_level' => $request->grade_level,
                'mobile_number' => $request->mobile_number,
                'parent_name' => $request->parent_name,
                'parent_email' => $request->parent_email,
                'parent_mobile' => $request->parent_mobile,
                'is_active' => $request->get('is_active', true)
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student created successfully',
                'student' => $student->load('user')
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error creating student: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update student information
     */
    public function updateStudent(Request $request, $id)
    {
        $student = Student::with('user')->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($student->user_id)],
            'band_level' => 'required|in:band6,band7,band8,band9',
            'school_name' => 'nullable|string|max:255',
            'class_name' => 'nullable|string|max:255',
            'grade_level' => 'nullable|string|max:255',
            'mobile_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'parent_name' => 'nullable|string|max:255',
            'parent_email' => 'nullable|email|max:255',
            'parent_mobile' => 'nullable|string|max:20',
            'is_active' => 'boolean'
        ]);

        DB::beginTransaction();
        try {
            // Update user account
            $student->user->update([
                'name' => $request->name,
                'email' => $request->email,
                'is_active' => $request->get('is_active', $student->is_active)
            ]);

            // Update student profile
            $student->update([
                'band_level' => $request->band_level,
                'school_name' => $request->school_name,
                'class_name' => $request->class_name,
                'grade_level' => $request->grade_level,
                'mobile_number' => $request->mobile_number,
                'address' => $request->address,
                'city' => $request->city,
                'parent_name' => $request->parent_name,
                'parent_email' => $request->parent_email,
                'parent_mobile' => $request->parent_mobile,
                'is_active' => $request->get('is_active', $student->is_active)
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Student updated successfully',
                'student' => $student->fresh()->load('user')
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error updating student: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update student password
     */
    public function updateStudentPassword(Request $request, $id)
    {
        $student = User::students()->findOrFail($id);

        $request->validate([
            'password' => 'required|string|min:8|confirmed'
        ]);

        $student->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student password updated successfully'
        ]);
    }

    /**
     * Toggle student active status
     */
    public function toggleStudentStatus($id)
    {
        $student = User::students()->findOrFail($id);
        
        $student->update([
            'is_active' => !$student->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student status updated successfully',
            'student' => $student
        ]);
    }

    /**
     * Delete student
     */
    public function deleteStudent($id)
    {
        $student = User::students()->findOrFail($id);
        $student->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully'
        ]);
    }

    /**
     * Get student statistics
     */
    public function getStudentStats($id)
    {
        $student = User::students()->with([
            'attempts.questions',
            'submissions',
            'mockTestAttempts'
        ])->findOrFail($id);

        $stats = [
            'total_attempts' => $student->attempts->count(),
            'total_submissions' => $student->submissions->count(),
            'total_mock_tests' => $student->mockTestAttempts->count(),
            'band_level' => $student->getBandLevelDisplay(),
            'last_activity' => $student->attempts->max('created_at') ?? $student->submissions->max('created_at'),
            'performance_by_module' => []
        ];

        // Calculate performance by module
        foreach (['reading', 'writing', 'listening', 'speaking'] as $module) {
            $moduleAttempts = $student->attempts()->whereHas('questions', function($q) use ($module) {
                $q->whereHas('readingPassage', function($rq) use ($module) {
                    $rq->whereHas('modules', function($mq) use ($module) {
                        $mq->where('name', $module);
                    });
                });
            })->get();

            $stats['performance_by_module'][$module] = [
                'attempts' => $moduleAttempts->count(),
                'avg_score' => $moduleAttempts->avg('score') ?? 0
            ];
        }

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats()
    {
        try {
            $stats = [
                'total_students' => User::where('role', 'student')->count(),
                'active_students' => User::where('role', 'student')->where('is_active', true)->count(),
                'students_by_band' => [
                    'band6' => User::where('role', 'student')->where('band_level', 'band6')->count(),
                    'band7' => User::where('role', 'student')->where('band_level', 'band7')->count(),
                    'band8' => User::where('role', 'student')->where('band_level', 'band8')->count(),
                    'band9' => User::where('role', 'student')->where('band_level', 'band9')->count(),
                ],
                'recent_registrations' => User::where('role', 'student')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count()
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getDashboardStats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => [
                    'total_students' => 0,
                    'active_students' => 0,
                    'students_by_band' => ['band6' => 0, 'band7' => 0, 'band8' => 0, 'band9' => 0],
                    'recent_registrations' => 0
                ]
            ], 500);
        }
    }

    /**
     * Bulk update student band levels
     */
    public function bulkUpdateBandLevel(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'band_level' => 'required|in:band6,band7,band8,band9'
        ]);

        Student::whereIn('id', $request->student_ids)
            ->update(['band_level' => $request->band_level]);

        return response()->json([
            'success' => true,
            'message' => 'Band levels updated successfully'
        ]);
    }

    /**
     * Get users without student profiles
     */
    public function getUsersWithoutStudentProfile()
    {
        try {
            \Log::info('getUsersWithoutStudentProfile called');
            
            // First, let's check if students table exists
            if (!Schema::hasTable('students')) {
                \Log::warning('Students table does not exist, returning all student users');
                // If students table doesn't exist, return all student users
                $users = User::where('role', 'student')
                    ->select('id', 'name', 'email')
                    ->get();
                    
                return response()->json([
                    'success' => true,
                    'message' => 'Students table not found. Showing all student users. Please run migrations.',
                    'users' => $users
                ]);
            }

            $users = User::where('role', 'student')
                ->whereDoesntHave('student')
                ->select('id', 'name', 'email')
                ->get();

            \Log::info('Found ' . $users->count() . ' users without student profiles');

            return response()->json([
                'success' => true,
                'users' => $users
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getUsersWithoutStudentProfile: ' . $e->getMessage());
            
            // Fallback: return all student users
            try {
                $users = User::where('role', 'student')
                    ->select('id', 'name', 'email')
                    ->get();
                    
                return response()->json([
                    'success' => true,
                    'message' => 'Fallback: Showing all student users',
                    'users' => $users
                ]);
            } catch (\Exception $fallbackError) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error loading users: ' . $e->getMessage(),
                    'users' => []
                ], 500);
            }
        }
    }

    /**
     * Link existing user to student profile
     */
    public function linkUserToStudent(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'band_level' => 'required|in:band6,band7,band8,band9',
            'school_name' => 'nullable|string|max:255',
            'class_name' => 'nullable|string|max:255',
            'mobile_number' => 'nullable|string|max:20'
        ]);

        try {
            // Check if user already has a student profile
            $existingStudent = Student::where('user_id', $request->user_id)->first();
            if ($existingStudent) {
                return response()->json([
                    'success' => false,
                    'message' => 'This user already has a student profile'
                ], 400);
            }

            // Create student profile
            $student = Student::create([
                'user_id' => $request->user_id,
                'band_level' => $request->band_level,
                'school_name' => $request->school_name,
                'class_name' => $request->class_name,
                'mobile_number' => $request->mobile_number,
                'is_active' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User successfully linked to student profile',
                'student' => $student->load('user')
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error linking user to student: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error linking user to student profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}