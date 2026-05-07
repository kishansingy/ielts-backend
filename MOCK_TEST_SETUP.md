# Mock Test System — Setup & Operations Guide

## Overview

The mock test system works in two phases:

| Phase | What | When |
|-------|------|------|
| **Initial seed** | 4 static tests (1 per band) — no API needed | Fresh deployment |
| **Daily scheduler** | 1 AI-generated test per day (rotates bands) | Every day at 3 AM |

After 20 days you have 20 tests per band (80 total). After 80 days, 80 per band (320 total).
All free tier — zero cost.

---

## Fresh Deployment (First Time)

### 1. Run migrations
```bash
php artisan migrate
```

### 2. Seed initial content (no API key needed)
```bash
php artisan db:seed --class=InitialMockTestSeeder
```
This creates 4 complete mock tests immediately — one per band level — with real IELTS-standard
reading passages, listening transcripts, writing tasks, and speaking prompts.

### 3. Verify
```bash
php artisan mocktests:audit
```

---

## Daily AI Generation (Automated)

### How it works
- Runs at **3:00 AM daily**
- Generates **1 test per day** for a rotating band:
  - Sunday → band6, Monday → band7, Tuesday → band8, Wednesday → band9, repeats
- Makes exactly **4 Gemini API calls** per run (one per section)
- Stays within Gemini free tier limits (10 RPM, daily quota)

### Setup cron (run once on your server)
```bash
crontab -e
```
Add this line:
```
* * * * * cd /opt/lampp/htdocs/IELTS-Project/backend && php artisan schedule:run >> /dev/null 2>&1
```

### Required .env variables
```env
GEMINI_API_KEY=your_key_here
GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta
GEMINI_MODEL=gemini-2.0-flash
```

---

## Manual Commands

### Generate a test for a specific band right now
```bash
php artisan mocktests:generate-daily --band=band6
php artisan mocktests:generate-daily --band=band7
php artisan mocktests:generate-daily --band=band8
php artisan mocktests:generate-daily --band=band9
```
> Wait at least 5 minutes between bands to avoid hitting the per-minute quota.

### Preview what would be generated (no API calls, no DB writes)
```bash
php artisan mocktests:generate-daily --dry-run
```

### Audit content quality
```bash
# Full audit — checks duplicate options, cross-band reuse, band consistency
php artisan mocktests:audit

# Audit one band only
php artisan mocktests:audit --band=band6

# Export as JSON
php artisan mocktests:audit --json > audit-report.json

# Auto-fix duplicate options
php artisan mocktests:audit --fix-options
```

### Clear all mock test content (DESTRUCTIVE — use with caution)
```bash
php artisan db:seed --class=ClearMockTestContentSeeder
```
> This truncates mock_tests, mock_test_sections, questions, reading_passages,
> listening_exercises, writing_tasks, speaking_prompts, and mock_test_attempts.
> Run InitialMockTestSeeder immediately after to restore base content.

---

## Band Level Standards

| Band | IELTS Score | Difficulty | Vocabulary | Sentence Complexity |
|------|-------------|------------|------------|---------------------|
| band6 | 6.5 | Beginner | Everyday words | Simple, clear sentences |
| band7 | 7.0 | Intermediate | Academic vocabulary | Complex sentences, subordinate clauses |
| band8 | 7.5 | Advanced | Sophisticated academic | Dense, nuanced argumentation |
| band9 | 8.0 | Proficient | Specialist/philosophical | Abstract, multi-layered discourse |

---

## Troubleshooting

### "Daily quota exhausted"
The Gemini free tier resets at midnight UTC. Wait until the next day.
The scheduler will retry automatically at 3 AM.

### "All models exhausted"
Check your quota at: https://ai.dev/rate-limit
Only `gemini-2.0-flash` and `gemini-2.5-flash` are used (both have free tier quota).
`gemini-2.5-pro` is intentionally excluded — it has 0 free tier quota.

### Test shows 0 questions
Run the audit command to identify passages without questions:
```bash
php artisan mocktests:audit
```

### Scheduler not running
Verify cron is active:
```bash
crontab -l
# Should show the * * * * * schedule:run line

# Check scheduler log
tail -f storage/logs/mock-test-generation.log
```

---

## Growth Timeline

| Days | Tests per band | Total tests |
|------|---------------|-------------|
| 0 (after seed) | 1 | 4 |
| 7 | ~2-3 | ~10 |
| 20 | 5 | 20 |
| 40 | 10 | 40 |
| 80 | 20 | 80 |
| 160 | 40 | 160 |

Each test is unique — AI generates different topics, passages, and questions every day
based on rotating topic pools and the current test number as a seed for variation.
