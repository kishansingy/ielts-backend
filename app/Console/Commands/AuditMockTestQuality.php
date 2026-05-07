<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MockTest;
use App\Models\Question;
use App\Models\ListeningQuestion;
use App\Models\ReadingPassage;
use App\Models\ListeningExercise;

class AuditMockTestQuality extends Command
{
    protected $signature = 'mocktests:audit
                            {--band= : Audit a specific band level (band6,band7,band8,band9)}
                            {--fix-options : Auto-fix duplicate options by shuffling}
                            {--json : Output results as JSON}';

    protected $description = 'Audit mock test content quality: band-level accuracy, duplicate options, cross-band question reuse';

    private array $report = [];
    private int $totalIssues = 0;

    public function handle(): int
    {
        $bandFilter = $this->option('band');
        $bands = $bandFilter ? [$bandFilter] : ['band6', 'band7', 'band8', 'band9'];

        $this->info('');
        $this->info('=== Mock Test Quality Audit ===');
        $this->info('');

        $this->auditDuplicateOptions($bands);
        $this->auditCrossBandQuestionReuse($bands);
        $this->auditBandLevelConsistency($bands);
        $this->auditMockTestCoverage($bands);

        if ($this->option('json')) {
            $this->line(json_encode($this->report, JSON_PRETTY_PRINT));
        } else {
            $this->printSummary();
        }

        if ($this->option('fix-options')) {
            $this->fixDuplicateOptions($bands);
        }

        return $this->totalIssues > 0 ? 1 : 0;
    }

    // ─── 1. Duplicate Options ────────────────────────────────────────────────

    private function auditDuplicateOptions(array $bands): void
    {
        $this->info('Checking for duplicate/identical options in questions...');

        $issues = [];

        // Reading questions
        $readingQuestions = Question::whereNotNull('options')
            ->whereHas('passage', fn($q) => $q->whereIn('band_level', $bands))
            ->with('passage:id,title,band_level')
            ->get();

        foreach ($readingQuestions as $q) {
            $dupes = $this->findDuplicateOptions($q->options);
            if ($dupes) {
                $issues[] = [
                    'type'     => 'reading',
                    'id'       => $q->id,
                    'band'     => $q->passage->band_level ?? 'unknown',
                    'passage'  => $q->passage->title ?? 'unknown',
                    'question' => substr($q->question_text, 0, 80),
                    'options'  => $q->options,
                    'dupes'    => $dupes,
                ];
            }
        }

        // Listening questions
        $listeningQuestions = ListeningQuestion::whereNotNull('options')
            ->whereHas('listeningExercise', fn($q) => $q->whereIn('band_level', $bands))
            ->with('listeningExercise:id,title,band_level')
            ->get();

        foreach ($listeningQuestions as $q) {
            $dupes = $this->findDuplicateOptions($q->options);
            if ($dupes) {
                $issues[] = [
                    'type'     => 'listening',
                    'id'       => $q->id,
                    'band'     => $q->listeningExercise->band_level ?? 'unknown',
                    'exercise' => $q->listeningExercise->title ?? 'unknown',
                    'question' => substr($q->question_text, 0, 80),
                    'options'  => $q->options,
                    'dupes'    => $dupes,
                ];
            }
        }

        $this->report['duplicate_options'] = $issues;
        $this->totalIssues += count($issues);

        if (empty($issues)) {
            $this->line('  <fg=green>✓ No duplicate options found</>');
        } else {
            $this->line("  <fg=red>✗ Found " . count($issues) . " questions with duplicate options:</>");
            foreach ($issues as $issue) {
                $this->line("    [{$issue['type']}] ID:{$issue['id']} Band:{$issue['band']} — \"{$issue['question']}\"");
                $this->line("      Options: " . implode(' | ', $issue['options']));
                $this->line("      Duplicates: " . implode(', ', $issue['dupes']));
            }
        }
        $this->info('');
    }

    private function findDuplicateOptions(?array $options): array
    {
        if (!$options || count($options) < 2) return [];

        $normalized = array_map(fn($o) => strtolower(trim($o)), $options);
        $counts = array_count_values($normalized);
        $dupes = [];
        foreach ($counts as $val => $count) {
            if ($count > 1) $dupes[] = $val;
        }
        return $dupes;
    }

    // ─── 2. Cross-Band Question Reuse ────────────────────────────────────────

    private function auditCrossBandQuestionReuse(array $bands): void
    {
        $this->info('Checking for identical questions reused across band levels...');

        $issues = [];

        // Reading: group by question_text, check if same text appears in multiple bands
        $readingGroups = Question::select('question_text')
            ->selectRaw('COUNT(DISTINCT rp.band_level) as band_count')
            ->selectRaw('GROUP_CONCAT(DISTINCT rp.band_level ORDER BY rp.band_level) as bands_used')
            ->join('reading_passages as rp', 'questions.passage_id', '=', 'rp.id')
            ->whereIn('rp.band_level', $bands)
            ->groupBy('question_text')
            ->havingRaw('COUNT(DISTINCT rp.band_level) > 1')
            ->get();

        foreach ($readingGroups as $row) {
            $issues[] = [
                'type'       => 'reading',
                'question'   => substr($row->question_text, 0, 100),
                'band_count' => $row->band_count,
                'bands'      => $row->bands_used,
            ];
        }

        // Listening: same check
        $listeningGroups = ListeningQuestion::select('question_text')
            ->selectRaw('COUNT(DISTINCT le.band_level) as band_count')
            ->selectRaw('GROUP_CONCAT(DISTINCT le.band_level ORDER BY le.band_level) as bands_used')
            ->join('listening_exercises as le', 'listening_questions.listening_exercise_id', '=', 'le.id')
            ->whereIn('le.band_level', $bands)
            ->groupBy('question_text')
            ->havingRaw('COUNT(DISTINCT le.band_level) > 1')
            ->get();

        foreach ($listeningGroups as $row) {
            $issues[] = [
                'type'       => 'listening',
                'question'   => substr($row->question_text, 0, 100),
                'band_count' => $row->band_count,
                'bands'      => $row->bands_used,
            ];
        }

        $this->report['cross_band_reuse'] = $issues;
        $this->totalIssues += count($issues);

        if (empty($issues)) {
            $this->line('  <fg=green>✓ No cross-band question reuse detected</>');
        } else {
            $this->line("  <fg=red>✗ Found " . count($issues) . " questions reused across multiple bands:</>");
            foreach ($issues as $issue) {
                $this->line("    [{$issue['type']}] Used in {$issue['band_count']} bands ({$issue['bands']}): \"{$issue['question']}\"");
            }
        }
        $this->info('');
    }

    // ─── 3. Band-Level Consistency ───────────────────────────────────────────

    private function auditBandLevelConsistency(array $bands): void
    {
        $this->info('Checking band-level consistency (passages vs questions vs mock tests)...');

        $issues = [];

        foreach ($bands as $band) {
            // Check reading passages that have questions tagged with a different band
            $mismatchedQuestions = Question::whereHas('passage', fn($q) => $q->where('band_level', $band))
                ->whereNotNull('ielts_band_level')
                ->where('ielts_band_level', '!=', str_replace('band', '', $band))
                ->count();

            if ($mismatchedQuestions > 0) {
                $issues[] = [
                    'band'    => $band,
                    'type'    => 'question_band_mismatch',
                    'message' => "{$mismatchedQuestions} reading questions have ielts_band_level that doesn't match their passage band_level",
                ];
            }

            // Check mock tests that reference content from a different band
            $mockTests = MockTest::where('band_level', $band)
                ->with('sections')
                ->get();

            foreach ($mockTests as $test) {
                foreach ($test->sections as $section) {
                    if ($section->module_type === 'reading') {
                        $passage = ReadingPassage::find($section->content_id);
                        if ($passage && $passage->band_level !== $band) {
                            $issues[] = [
                                'band'    => $band,
                                'type'    => 'section_band_mismatch',
                                'message' => "Mock Test #{$test->id} \"{$test->title}\" uses reading passage \"{$passage->title}\" (band: {$passage->band_level})",
                            ];
                        }
                    } elseif ($section->module_type === 'listening') {
                        $exercise = ListeningExercise::find($section->content_id);
                        if ($exercise && $exercise->band_level !== $band) {
                            $issues[] = [
                                'band'    => $band,
                                'type'    => 'section_band_mismatch',
                                'message' => "Mock Test #{$test->id} \"{$test->title}\" uses listening exercise \"{$exercise->title}\" (band: {$exercise->band_level})",
                            ];
                        }
                    }
                }
            }
        }

        $this->report['band_consistency'] = $issues;
        $this->totalIssues += count($issues);

        if (empty($issues)) {
            $this->line('  <fg=green>✓ Band-level consistency looks good</>');
        } else {
            $this->line("  <fg=red>✗ Found " . count($issues) . " band-level consistency issues:</>");
            foreach ($issues as $issue) {
                $this->line("    [{$issue['band']}] {$issue['message']}");
            }
        }
        $this->info('');
    }

    // ─── 4. Mock Test Coverage ───────────────────────────────────────────────

    private function auditMockTestCoverage(array $bands): void
    {
        $this->info('Checking mock test content coverage per band...');

        $stats = [];

        foreach ($bands as $band) {
            $tests = MockTest::where('band_level', $band)->where('is_active', true)->count();
            $passages = ReadingPassage::where('band_level', $band)->count();
            $passagesWithQuestions = ReadingPassage::where('band_level', $band)
                ->whereHas('questions')
                ->count();
            $passagesWithoutQuestions = $passages - $passagesWithQuestions;
            $totalReadingQuestions = Question::whereHas('passage', fn($q) => $q->where('band_level', $band))->count();
            $listeningExercises = ListeningExercise::where('band_level', $band)->count();
            $listeningWithQuestions = ListeningExercise::where('band_level', $band)
                ->whereHas('questions')
                ->count();
            $totalListeningQuestions = ListeningQuestion::whereHas('listeningExercise', fn($q) => $q->where('band_level', $band))->count();

            $stats[$band] = [
                'active_mock_tests'              => $tests,
                'reading_passages'               => $passages,
                'passages_with_questions'        => $passagesWithQuestions,
                'passages_without_questions'     => $passagesWithoutQuestions,
                'total_reading_questions'        => $totalReadingQuestions,
                'listening_exercises'            => $listeningExercises,
                'exercises_with_questions'       => $listeningWithQuestions,
                'total_listening_questions'      => $totalListeningQuestions,
            ];

            $bandLabel = strtoupper($band);
            $this->line("  <fg=cyan>{$bandLabel}</>: {$tests} tests | Reading: {$passages} passages ({$totalReadingQuestions} Qs) | Listening: {$listeningExercises} exercises ({$totalListeningQuestions} Qs)");

            if ($passagesWithoutQuestions > 0) {
                $this->line("    <fg=yellow>⚠ {$passagesWithoutQuestions} reading passages have NO questions</>");
                $this->totalIssues += $passagesWithoutQuestions;
            }
            if ($listeningExercises > $listeningWithQuestions) {
                $noQ = $listeningExercises - $listeningWithQuestions;
                $this->line("    <fg=yellow>⚠ {$noQ} listening exercises have NO questions</>");
                $this->totalIssues += $noQ;
            }
        }

        $this->report['coverage'] = $stats;
        $this->info('');
    }

    // ─── 5. Auto-fix Duplicate Options ───────────────────────────────────────

    private function fixDuplicateOptions(array $bands): void
    {
        $this->info('Auto-fixing duplicate options (appending letter suffix to duplicates)...');
        $fixed = 0;

        $questions = Question::whereNotNull('options')
            ->whereHas('passage', fn($q) => $q->whereIn('band_level', $bands))
            ->get();

        foreach ($questions as $q) {
            if ($this->findDuplicateOptions($q->options)) {
                $q->options = $this->deduplicateOptions($q->options);
                $q->save();
                $fixed++;
            }
        }

        $lQuestions = ListeningQuestion::whereNotNull('options')
            ->whereHas('listeningExercise', fn($q) => $q->whereIn('band_level', $bands))
            ->get();

        foreach ($lQuestions as $q) {
            if ($this->findDuplicateOptions($q->options)) {
                $q->options = $this->deduplicateOptions($q->options);
                $q->save();
                $fixed++;
            }
        }

        $this->info("  Fixed {$fixed} questions with duplicate options.");
    }

    private function deduplicateOptions(array $options): array
    {
        $seen = [];
        $result = [];
        foreach ($options as $opt) {
            $key = strtolower(trim($opt));
            if (isset($seen[$key])) {
                $seen[$key]++;
                $result[] = $opt . ' (' . chr(64 + $seen[$key]) . ')';
            } else {
                $seen[$key] = 1;
                $result[] = $opt;
            }
        }
        return $result;
    }

    // ─── Summary ─────────────────────────────────────────────────────────────

    private function printSummary(): void
    {
        $this->info('=== Summary ===');
        $dupeCount   = count($this->report['duplicate_options'] ?? []);
        $reuseCount  = count($this->report['cross_band_reuse'] ?? []);
        $bandCount   = count($this->report['band_consistency'] ?? []);

        $this->line("  Duplicate options:          <fg=" . ($dupeCount  ? 'red' : 'green') . ">{$dupeCount}</>");
        $this->line("  Cross-band question reuse:  <fg=" . ($reuseCount ? 'red' : 'green') . ">{$reuseCount}</>");
        $this->line("  Band consistency issues:    <fg=" . ($bandCount  ? 'red' : 'green') . ">{$bandCount}</>");
        $this->info('');

        if ($this->totalIssues === 0) {
            $this->info('<fg=green>All checks passed. Content quality looks good.</>');
        } else {
            $this->warn("Total issues found: {$this->totalIssues}");
            $this->line('');
            $this->line('Tips to fix:');
            $this->line('  • Run with --fix-options to auto-fix duplicate options');
            $this->line('  • Re-seed with band-specific content using ProductionIELTSQuestionsSeeder');
            $this->line('  • Use --json flag to export full report for review');
        }
    }
}
