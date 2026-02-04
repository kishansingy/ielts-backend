<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BandLevelController extends Controller
{
    /**
     * Get all available band levels
     */
    public function getBandLevels()
    {
        $bandLevels = [
            'band6' => [
                'code' => 'band6',
                'name' => 'Band 6',
                'description' => 'Competent User - Generally effective command of the language'
            ],
            'band7' => [
                'code' => 'band7',
                'name' => 'Band 7',
                'description' => 'Good User - Operational command of the language'
            ],
            'band8' => [
                'code' => 'band8',
                'name' => 'Band 8',
                'description' => 'Very Good User - Fully operational command with occasional inaccuracies'
            ],
            'band9' => [
                'code' => 'band9',
                'name' => 'Band 9',
                'description' => 'Expert User - Fully operational command of the language'
            ]
        ];

        return response()->json([
            'success' => true,
            'band_levels' => array_values($bandLevels)
        ]);
    }

    /**
     * Assign band level to a student
     */
    public function assignBandLevel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'band_level' => 'required|in:band6,band7,band8,band9',
            'school_name' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        $user = User::find($request->user_id);

        if ($user->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Band levels can only be assigned to students'
            ], 400);
        }

        $user->update([
            'band_level' => $request->band_level,
            'school_name' => $request->school_name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Band level assigned successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'band_level' => $user->band_level,
                'band_level_display' => $user->getBandLevelDisplay(),
                'school_name' => $user->school_name
            ]
        ]);
    }

    /**
     * Get students by band level
     */
    public function getStudentsByBandLevel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'band_level' => 'nullable|in:band6,band7,band8,band9',
            'school_name' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        $query = User::students()->active();

        if ($request->band_level) {
            $query->byBandLevel($request->band_level);
        }

        if ($request->school_name) {
            $query->bySchool($request->school_name);
        }

        $students = $query->select('id', 'name', 'email', 'band_level', 'school_name', 'created_at')
                         ->get()
                         ->map(function ($student) {
                             return [
                                 'id' => $student->id,
                                 'name' => $student->name,
                                 'email' => $student->email,
                                 'band_level' => $student->band_level,
                                 'band_level_display' => $student->getBandLevelDisplay(),
                                 'school_name' => $student->school_name,
                                 'created_at' => $student->created_at
                             ];
                         });

        return response()->json([
            'success' => true,
            'students' => $students
        ]);
    }

    /**
     * Update student band level
     */
    public function updateBandLevel(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'band_level' => 'required|in:band6,band7,band8,band9',
            'school_name' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Band levels can only be assigned to students'
            ], 400);
        }

        $user->update([
            'band_level' => $request->band_level,
            'school_name' => $request->school_name
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Band level updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'band_level' => $user->band_level,
                'band_level_display' => $user->getBandLevelDisplay(),
                'school_name' => $user->school_name
            ]
        ]);
    }

    /**
     * Activate/Deactivate student
     */
    public function toggleStudentStatus(Request $request, $userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        if ($user->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Only student accounts can be activated/deactivated'
            ], 400);
        }

        $user->update([
            'is_active' => !$user->is_active
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student status updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'status' => $user->is_active ? 'Active' : 'Inactive'
            ]
        ]);
    }

    /**
     * Get band level statistics
     */
    public function getBandLevelStats()
    {
        $stats = [
            'band6' => User::students()->byBandLevel('band6')->active()->count(),
            'band7' => User::students()->byBandLevel('band7')->active()->count(),
            'band8' => User::students()->byBandLevel('band8')->active()->count(),
            'band9' => User::students()->byBandLevel('band9')->active()->count(),
            'unassigned' => User::students()->whereNull('band_level')->active()->count(),
            'inactive' => User::students()->where('is_active', false)->count(),
            'total_students' => User::students()->count()
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }
}