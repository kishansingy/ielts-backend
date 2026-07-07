<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\ListeningExercise;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔧 Fixing audio file path...\n";

try {
    $exercise = ListeningExercise::where('title', 'University Enrollment - Accuracy Test')->first();
    
    if ($exercise) {
        $exercise->update([
            'audio_file_path' => 'listening/audio/XperGRm0EKQHP7vRsFJUyvBZuVclkDTQAa5PocvB.mp3'
        ]);
        echo "✅ Audio file path updated successfully!\n";
        echo "📁 New path: listening/audio/XperGRm0EKQHP7vRsFJUyvBZuVclkDTQAa5PocvB.mp3\n";
    } else {
        echo "❌ Exercise not found\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}