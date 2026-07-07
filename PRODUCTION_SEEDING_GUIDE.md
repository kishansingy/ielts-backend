# Production Data Seeding Guide

This guide explains how to seed your production database with IELTS exam questions and mock tests.

## Overview

The production seeders create:
- **Modules**: Reading, Listening, Writing, and Speaking modules for each band level
- **Questions**: 20 real IELTS-style questions per module per band level
- **Mock Tests**: 20 complete mock tests for each band level (band6, band7, band8, band9)

Each mock test includes:
- 3 Reading passages with questions
- 4 Listening exercises with questions
- 2 Writing tasks (Task 1 and Task 2)
- 3 Speaking prompts (Part 1, 2, and 3)

## Seeders Available

### 1. ProductionIELTSQuestionsSeeder
Creates all IELTS questions organized by module type and band level.

### 2. ProductionMockTestsSeeder
Creates 20 complete mock tests for each band level (80 mock tests total).

### 3. ProductionSeeder
Runs both seeders above in the correct order.

## How to Run

### Option 1: Run All Production Seeders (Recommended)
```bash
cd backend
php artisan db:seed --class=ProductionSeeder
```

### Option 2: Run Individual Seeders
```bash
# Seed only questions
php artisan db:seed --class=ProductionIELTSQuestionsSeeder

# Seed only mock tests
php artisan db:seed --class=ProductionMockTestsSeeder
```

### Option 3: Run All Seeders (Including Development Data)
```bash
php artisan db:seed
```

## Production Deployment Steps

1. **Backup your database** (if you have existing data)
   ```bash
   php artisan backup:run
   ```

2. **Run migrations** (if not already done)
   ```bash
   php artisan migrate
   ```

3. **Seed production data**
   ```bash
   php artisan db:seed --class=ProductionSeeder
   ```

4. **Verify the data**
   ```bash
   php artisan tinker
   ```
   Then in tinker:
   ```php
   // Check modules
   \App\Models\Module::count();
   
   // Check questions
   \App\Models\Question::count();
   
   // Check mock tests
   \App\Models\MockTest::count();
   
   // Check mock tests by band level
   \App\Models\MockTest::where('band_level', 'band6')->count();
   ```

## Data Structure

### Band Levels
- `band6`: Intermediate level
- `band7`: Upper-intermediate level
- `band8`: Advanced level
- `band9`: Expert level

### Module Types
- `reading`: Reading comprehension passages and questions
- `listening`: Audio exercises with questions
- `writing`: Task 1 (descriptive) and Task 2 (essay)
- `speaking`: Speaking prompts for practice

### Question Types
- `multiple_choice`: Multiple choice questions
- `short_answer`: Short answer questions
- `true_false`: True/False questions
- `fill_in_blank`: Fill in the blank questions

## Customization

To customize the questions or add more:

1. Edit `backend/database/seeders/ProductionIELTSQuestionsSeeder.php`
2. Modify the methods:
   - `getReadingPassages()` - Add/edit reading passages
   - `getListeningExercises()` - Add/edit listening exercises
   - `getWritingTasks()` - Add/edit writing tasks
   - `getSpeakingPrompts()` - Add/edit speaking prompts

3. Edit `backend/database/seeders/ProductionMockTestsSeeder.php`
4. Modify the prompt generation methods to add more variety

## Important Notes

- All questions are based on real IELTS exam repeated topics
- Questions are designed to match the difficulty of each band level
- Mock tests follow the official IELTS test format and timing
- Audio files for listening exercises need to be uploaded separately to `storage/app/public/listening/`
- The seeders use database transactions for data integrity

## Troubleshooting

### Issue: Seeder fails with foreign key constraint error
**Solution**: Make sure migrations are run first and tables exist.

### Issue: Duplicate entry errors
**Solution**: The seeders use `firstOrCreate` for modules to prevent duplicates. If you need to re-seed, truncate the tables first:
```bash
php artisan migrate:fresh
php artisan db:seed --class=ProductionSeeder
```

### Issue: Not enough questions/passages
**Solution**: The seeders will automatically create additional content if needed. Check the seeder methods for content generation logic.

## Audio Files Setup

For listening exercises, you need to upload audio files to:
```
storage/app/public/listening/
```

File naming convention:
- `accommodation_inquiry.mp3`
- `library_tour.mp3`
- `job_interview.mp3`
- `conservation_lecture.mp3`
- `mock_test_{testNumber}_section_{sectionNumber}.mp3`

Make sure to run:
```bash
php artisan storage:link
```

## Support

For issues or questions, check:
- Laravel logs: `storage/logs/laravel.log`
- Database connection: `php artisan tinker` then `DB::connection()->getPdo();`
- Seeder output for specific error messages
