# PHPUnit Testing Guide

## Overview
This document describes the PHPUnit tests for the IELTS Learning Platform API.

## Test Coverage

### 1. Authentication Tests (`AuthenticationTest.php`)
- User registration
- User login with valid credentials
- User login with invalid credentials
- Get authenticated user profile
- User logout
- Guest access restrictions

### 2. Reading Management Tests (`ReadingManagementTest.php`)
- Admin can list reading passages
- Admin can create reading passage
- Admin can update reading passage
- Admin can delete reading passage
- Student cannot access admin endpoints
- Guest cannot access admin endpoints

### 3. Writing Management Tests (`WritingManagementTest.php`)
- Admin can list writing tasks
- Admin can create writing task
- Admin can update writing task
- Admin can delete writing task
- Student cannot access admin endpoints

### 4. Listening Management Tests (`ListeningManagementTest.php`)
- Admin can list listening exercises
- Admin can create listening exercise (with file upload)
- Admin can update listening exercise
- Admin can delete listening exercise
- Student cannot access admin endpoints

### 5. Speaking Management Tests (`SpeakingManagementTest.php`)
- Admin can list speaking prompts
- Admin can create speaking prompt
- Admin can update speaking prompt
- Admin can delete speaking prompt
- Student cannot access admin endpoints

## Running Tests

### Run All Tests
```bash
cd backend
php artisan test
```

### Run Specific Test File
```bash
php artisan test --filter=ReadingManagementTest
php artisan test --filter=WritingManagementTest
php artisan test --filter=ListeningManagementTest
php artisan test --filter=SpeakingManagementTest
php artisan test --filter=AuthenticationTest
```

### Run Specific Test Method
```bash
php artisan test --filter=admin_can_create_reading_passage
```

### Run Tests with Coverage
```bash
php artisan test --coverage
```

### Run Tests in Parallel
```bash
php artisan test --parallel
```

## Test Database Setup

The tests use the `RefreshDatabase` trait which:
1. Runs migrations before each test
2. Rolls back migrations after each test
3. Ensures a clean database state for each test

Make sure your `.env.testing` file is configured:

```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

Or use a separate testing database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ielts_testing
DB_USERNAME=root
DB_PASSWORD=
```

## Factories

Model factories are used to generate test data:

- `UserFactory` - Creates test users (admin/student)
- `ReadingPassageFactory` - Creates reading passages
- `WritingTaskFactory` - Creates writing tasks
- `ListeningExerciseFactory` - Creates listening exercises
- `SpeakingPromptFactory` - Creates speaking prompts

## Test Structure

Each test follows the AAA pattern:
1. **Arrange** - Set up test data and conditions
2. **Act** - Execute the code being tested
3. **Assert** - Verify the results

Example:
```php
/** @test */
public function admin_can_create_reading_passage()
{
    // Arrange
    $data = ['title' => 'Test', ...];
    
    // Act
    $response = $this->actingAs($this->admin, 'api')
        ->postJson('/api/admin/reading-passages', $data);
    
    // Assert
    $response->assertStatus(201);
    $this->assertDatabaseHas('reading_passages', ['title' => 'Test']);
}
```

## Continuous Integration

Add to your CI/CD pipeline:

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - name: Install Dependencies
        run: composer install
      - name: Run Tests
        run: php artisan test
```

## Best Practices

1. **Use factories** for test data generation
2. **Use RefreshDatabase** to ensure clean state
3. **Test both success and failure cases**
4. **Test authorization** (admin vs student vs guest)
5. **Use descriptive test names** that explain what is being tested
6. **Keep tests independent** - each test should work in isolation
7. **Mock external services** when necessary

## Troubleshooting

### Tests failing due to missing tables
```bash
php artisan migrate:fresh --env=testing
```

### Clear test cache
```bash
php artisan config:clear
php artisan cache:clear
```

### Debug specific test
```bash
php artisan test --filter=test_name --stop-on-failure
```
