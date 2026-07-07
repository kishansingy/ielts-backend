<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Mock Tests Debug ===\n\n";

// Check total mock tests
$totalTests = \App\Models\MockTest::count();
echo "Total Mock Tests: $totalTests\n";

// Check active tests
$activeTests = \App\Models\MockTest::where('is_active', true)->count();
echo "Active Mock Tests: $activeTests\n\n";

// Check tests by band level
foreach (['band6', 'band7', 'band8', 'band9'] as $band) {
    $count = \App\Models\MockTest::where('band_level', $band)
        ->where('is_active', true)
        ->count();
    echo "Active $band tests: $count\n";
}

echo "\n=== User Check ===\n";
// Check if there are any students
$students = \App\Models\User::where('role', 'student')->get();
echo "Total Students: " . $students->count() . "\n\n";

foreach ($students->take(5) as $student) {
    echo "Student: {$student->email}\n";
    echo "  Band Level: " . ($student->band_level ?? 'NOT SET') . "\n";
    echo "  Is Active: " . ($student->is_active ? 'Yes' : 'No') . "\n";
    
    // Check what tests they can access
    $accessibleTests = \App\Models\MockTest::accessibleByUser($student)->count();
    echo "  Accessible Tests: $accessibleTests\n\n";
}
