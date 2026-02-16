#!/bin/bash

# Production Data Seeding Script
# This script seeds the database with IELTS questions and mock tests

echo "=========================================="
echo "IELTS Production Data Seeding"
echo "=========================================="
echo ""

# Check if we're in the backend directory
if [ ! -f "artisan" ]; then
    echo "Error: This script must be run from the backend directory"
    exit 1
fi

# Ask for confirmation
echo "This will seed your database with:"
echo "  - IELTS questions for all modules (Reading, Listening, Writing, Speaking)"
echo "  - 20 questions per band level (band6, band7, band8, band9)"
echo "  - 20 complete mock tests per band level (80 total)"
echo ""
read -p "Do you want to continue? (y/n) " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Seeding cancelled."
    exit 0
fi

echo ""
echo "Starting production seeding..."
echo ""

# Run the production seeder
php artisan db:seed --class=ProductionSeeder

# Check if seeding was successful
if [ $? -eq 0 ]; then
    echo ""
    echo "=========================================="
    echo "✓ Seeding completed successfully!"
    echo "=========================================="
    echo ""
    echo "Verifying data..."
    echo ""
    
    # Show counts
    php artisan tinker --execute="
        echo 'Modules: ' . \App\Models\Module::count() . PHP_EOL;
        echo 'Reading Passages: ' . \App\Models\ReadingPassage::count() . PHP_EOL;
        echo 'Questions: ' . \App\Models\Question::count() . PHP_EOL;
        echo 'Listening Exercises: ' . \App\Models\ListeningExercise::count() . PHP_EOL;
        echo 'Writing Tasks: ' . \App\Models\WritingTask::count() . PHP_EOL;
        echo 'Speaking Prompts: ' . \App\Models\SpeakingPrompt::count() . PHP_EOL;
        echo 'Mock Tests: ' . \App\Models\MockTest::count() . PHP_EOL;
        echo PHP_EOL;
        echo 'Mock Tests by Band Level:' . PHP_EOL;
        echo '  Band 6: ' . \App\Models\MockTest::where('band_level', 'band6')->count() . PHP_EOL;
        echo '  Band 7: ' . \App\Models\MockTest::where('band_level', 'band7')->count() . PHP_EOL;
        echo '  Band 8: ' . \App\Models\MockTest::where('band_level', 'band8')->count() . PHP_EOL;
        echo '  Band 9: ' . \App\Models\MockTest::where('band_level', 'band9')->count() . PHP_EOL;
    "
    
    echo ""
    echo "Next steps:"
    echo "1. Upload audio files to storage/app/public/listening/"
    echo "2. Run: php artisan storage:link"
    echo "3. Test the application"
    echo ""
else
    echo ""
    echo "=========================================="
    echo "✗ Seeding failed!"
    echo "=========================================="
    echo ""
    echo "Please check the error messages above."
    echo "Common issues:"
    echo "  - Database connection not configured"
    echo "  - Migrations not run (run: php artisan migrate)"
    echo "  - Permission issues"
    echo ""
    exit 1
fi
