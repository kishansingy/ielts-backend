<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ReadingPassage;
use App\Models\Question;

echo "🧹 Cleaning up placeholder reading passages...\n\n";

// Find all passages with placeholder content
$placeholderPatterns = [
    'The passage discusses the evolution of artificial intelligence',
    'This text explores the importance of biodiversity',
    'The article examines the psychological effects of social media',
];

$deletedCount = 0;
$deletedQuestions = 0;

foreach ($placeholderPatterns as $pattern) {
    $passages = ReadingPassage::where('content', 'LIKE', $pattern . '%')->get();
    
    foreach ($passages as $passage) {
        echo "Deleting: ID {$passage->id} - {$passage->title}\n";
        
        // Count and delete questions
        $questionCount = $passage->questions()->count();
        $passage->questions()->delete();
        $deletedQuestions += $questionCount;
        
        // Delete passage
        $passage->delete();
        $deletedCount++;
    }
}

echo "\n✅ Cleanup complete!\n";
echo "   Deleted {$deletedCount} placeholder passages\n";
echo "   Deleted {$deletedQuestions} associated questions\n";
echo "\nRemaining passages: " . ReadingPassage::count() . "\n";
