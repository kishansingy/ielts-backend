#!/bin/bash

echo "Setting up AI Question Generation System..."

# Run migrations
echo "Running database migrations..."
php artisan migrate

# Clear cache
echo "Clearing application cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Create some sample modules if they don't exist
echo "Creating sample modules..."
php artisan tinker --execute="
if (App\Models\Module::count() == 0) {
    App\Models\Module::create([
        'name' => 'IELTS Reading Practice',
        'module_type' => 'reading',
        'description' => 'Reading comprehension practice with AI-generated questions',
        'supports_ai_generation' => true,
        'ai_generation_config' => [
            'question_types' => ['multiple_choice', 'true_false', 'fill_blank'],
            'difficulty_levels' => ['6', '7', '8', '9'],
            'questions_per_passage' => 10
        ],
        'is_active' => true
    ]);
    
    App\Models\Module::create([
        'name' => 'IELTS Listening Practice',
        'module_type' => 'listening',
        'description' => 'Listening comprehension with AI-generated questions',
        'supports_ai_generation' => true,
        'ai_generation_config' => [
            'question_types' => ['multiple_choice', 'fill_blank', 'short_answer'],
            'difficulty_levels' => ['6', '7', '8', '9'],
            'questions_per_exercise' => 8
        ],
        'is_active' => true
    ]);
    
    App\Models\Module::create([
        'name' => 'IELTS Writing Practice',
        'module_type' => 'writing',
        'description' => 'Writing tasks with AI-generated prompts',
        'supports_ai_generation' => true,
        'ai_generation_config' => [
            'task_types' => ['essay', 'letter', 'report'],
            'difficulty_levels' => ['6', '7', '8', '9'],
            'word_count_ranges' => ['150-250', '250-400']
        ],
        'is_active' => true
    ]);
    
    App\Models\Module::create([
        'name' => 'IELTS Speaking Practice',
        'module_type' => 'speaking',
        'description' => 'Speaking practice with AI-generated prompts',
        'supports_ai_generation' => true,
        'ai_generation_config' => [
            'question_types' => ['part1', 'part2', 'part3'],
            'difficulty_levels' => ['6', '7', '8', '9'],
            'topics' => ['personal', 'academic', 'general']
        ],
        'is_active' => true
    ]);
    
    echo 'Sample modules created successfully!';
} else {
    echo 'Modules already exist, skipping creation.';
}
"

echo "AI Question Generation System setup complete!"
echo ""
echo "Next steps:"
echo "1. Add your OpenAI API key to .env file:"
echo "   OPENAI_API_KEY=your_api_key_here"
echo ""
echo "2. Test the system by making API calls to:"
echo "   POST /api/ai-questions/preview"
echo "   POST /api/ai-questions/mock-tests/{id}/generate"
echo ""
echo "3. Check the documentation for API usage examples"