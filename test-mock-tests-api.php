<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Mock Tests API ===\n\n";

// Get a test student
$student = \App\Models\User::where('role', 'student')
    ->where('email', 'student1@example.com')
    ->first();

if (!$student) {
    echo "ERROR: No student found!\n";
    exit(1);
}

echo "Testing with student: {$student->email}\n";
echo "Student Band Level: " . ($student->band_level ?? 'NOT SET') . "\n";
echo "Student Active: " . ($student->is_active ? 'Yes' : 'No') . "\n\n";

// Simulate the API call
echo "=== Simulating API Call ===\n";

$query = \App\Models\MockTest::with('sections');

// Filter by user's band level if not admin
if (!$student->isAdmin()) {
    $query->accessibleByUser($student)->available();
}

$mockTests = $query->orderBy('created_at', 'desc')->get();

echo "Mock Tests Found: " . $mockTests->count() . "\n\n";

if ($mockTests->count() > 0) {
    echo "First 5 tests:\n";
    foreach ($mockTests->take(5) as $test) {
        echo "  - ID: {$test->id}\n";
        echo "    Title: {$test->title}\n";
        echo "    Band Level: {$test->band_level}\n";
        echo "    Active: " . ($test->is_active ? 'Yes' : 'No') . "\n";
        echo "    Sections: " . $test->sections->count() . "\n";
        echo "    Duration: {$test->duration_minutes} minutes\n\n";
    }
} else {
    echo "No tests found!\n";
    
    // Debug why
    echo "\n=== Debugging ===\n";
    $allTests = \App\Models\MockTest::count();
    echo "Total tests in DB: $allTests\n";
    
    $activeTests = \App\Models\MockTest::where('is_active', true)->count();
    echo "Active tests: $activeTests\n";
    
    $bandTests = \App\Models\MockTest::where('band_level', $student->band_level)
        ->where('is_active', true)
        ->count();
    echo "Tests for {$student->band_level}: $bandTests\n";
}

// Test creating a token for this user
echo "\n=== Creating Test Token ===\n";
$token = $student->createToken('test-token')->plainTextToken;
echo "Token created: " . substr($token, 0, 20) . "...\n";
echo "\nYou can test the API with:\n";
echo "curl -X GET \"http://localhost:8000/api/mock-tests\" \\\n";
echo "  -H \"Accept: application/json\" \\\n";
echo "  -H \"Authorization: Bearer {$token}\"\n";
