<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ReadingPassage;
use App\Models\Question;
use App\Models\ListeningExercise;
use App\Models\ListeningQuestion;
use Illuminate\Support\Facades\DB;

/**
 * Seeds band-differentiated questions.
 * Each band gets unique passages and questions appropriate to that difficulty.
 * Run: php artisan db:seed --class=BandDifferentiatedQuestionsSeeder
 */
class BandDifferentiatedQuestionsSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = \App\Models\User::firstOrCreate(
            ['email' => 'admin@ielts.com'],
            ['name' => 'System Admin', 'password' => bcrypt('password'), 'role' => 'admin']
        );

        DB::transaction(function () use ($adminUser) {
            foreach ($this->getBandContent() as $band => $content) {
                $this->command->info("Seeding {$band} content...");
                $this->seedReadingPassages($band, $content['reading'], $adminUser->id);
                $this->seedListeningExercises($band, $content['listening'], $adminUser->id);
            }
        });

        $this->command->info('Band-differentiated questions seeded successfully!');
    }

    private function seedReadingPassages(string $band, array $passages, int $adminId): void
    {
        foreach ($passages as $passageData) {
            // Skip if already exists (idempotent)
            $exists = ReadingPassage::where('title', $passageData['title'])
                ->where('band_level', $band)
                ->exists();
            if ($exists) continue;

            $passage = ReadingPassage::create([
                'title'            => $passageData['title'],
                'content'          => $passageData['content'],
                'difficulty_level' => $this->difficultyFor($band),
                'band_level'       => $band,
                'time_limit'       => 20,
                'created_by'       => $adminId,
            ]);

            foreach ($passageData['questions'] as $i => $q) {
                Question::create([
                    'passage_id'       => $passage->id,
                    'question_text'    => $q['text'],
                    'question_type'    => $q['type'],
                    'correct_answer'   => $q['answer'],
                    'options'          => $q['options'] ?? null,
                    'points'           => 1,
                    'ielts_band_level' => str_replace('band', '', $band),
                    'is_ai_generated'  => false,
                ]);
            }
        }
    }

    private function seedListeningExercises(string $band, array $exercises, int $adminId): void
    {
        foreach ($exercises as $exData) {
            $exists = ListeningExercise::where('title', $exData['title'])
                ->where('band_level', $band)
                ->exists();
            if ($exists) continue;

            $exercise = ListeningExercise::create([
                'title'            => $exData['title'],
                'audio_file_path'  => $exData['audio_path'],
                'transcript'       => $exData['transcript'],
                'duration'         => $exData['duration'],
                'difficulty_level' => $this->difficultyFor($band),
                'band_level'       => $band,
                'created_by'       => $adminId,
            ]);

            foreach ($exData['questions'] as $q) {
                ListeningQuestion::create([
                    'listening_exercise_id' => $exercise->id,
                    'question_text'         => $q['text'],
                    'question_type'         => $q['type'],
                    'correct_answer'        => $q['answer'],
                    'options'               => $q['options'] ?? null,
                    'points'                => 1,
                ]);
            }
        }
    }

    private function difficultyFor(string $band): string
    {
        return match ($band) {
            'band6' => 'beginner',
            'band7' => 'intermediate',
            'band8' => 'upper_intermediate',
            'band9' => 'advanced',
            default => 'intermediate',
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BAND-SPECIFIC CONTENT
    // Each band has unique passages, unique questions, and unique option sets.
    // Vocabulary and sentence complexity increase with band level.
    // ─────────────────────────────────────────────────────────────────────────

    private function getBandContent(): array
    {
        return [
            'band6' => [
                'reading' => [
                    [
                        'title'   => 'Band 6.5 - Daily Life and Technology',
                        'content' => "Many people use smartphones every day. These devices help people communicate with friends and family. They can also be used to find information on the internet. Some people think smartphones are very useful, but others worry that people spend too much time on them. Studies show that young people use their phones for about five hours a day. This can affect their sleep and their ability to concentrate at school or work. Experts suggest taking regular breaks from screens to stay healthy.",
                        'questions' => [
                            ['text' => 'What do smartphones help people do?', 'type' => 'multiple_choice', 'answer' => 'communicate with friends and family', 'options' => ['play games', 'communicate with friends and family', 'watch television', 'drive cars']],
                            ['text' => 'How many hours a day do young people use their phones?', 'type' => 'fill_blank', 'answer' => 'five hours'],
                            ['text' => 'What can too much phone use affect?', 'type' => 'multiple_choice', 'answer' => 'sleep and concentration', 'options' => ['appetite and weight', 'sleep and concentration', 'height and strength', 'hearing and vision']],
                            ['text' => 'What do experts suggest to stay healthy?', 'type' => 'fill_blank', 'answer' => 'taking regular breaks from screens'],
                            ['text' => 'True or False: Everyone agrees smartphones are useful.', 'type' => 'true_false', 'answer' => 'false', 'options' => ['true', 'false']],
                        ],
                    ],
                    [
                        'title'   => 'Band 6.5 - Public Transport in Cities',
                        'content' => "Public transport is important in big cities. Buses, trains, and trams help people travel without using a car. This reduces traffic and pollution. In many cities, the number of people using public transport has increased in recent years. Governments are investing money to improve bus and train services. However, some people still prefer to drive because it is more convenient. Making public transport cheaper and faster could encourage more people to use it.",
                        'questions' => [
                            ['text' => 'Name one type of public transport mentioned.', 'type' => 'multiple_choice', 'answer' => 'buses', 'options' => ['bicycles', 'buses', 'aeroplanes', 'ships']],
                            ['text' => 'What does using public transport reduce?', 'type' => 'fill_blank', 'answer' => 'traffic and pollution'],
                            ['text' => 'Why do some people prefer to drive?', 'type' => 'multiple_choice', 'answer' => 'it is more convenient', 'options' => ['it is cheaper', 'it is more convenient', 'it is faster', 'it is safer']],
                            ['text' => 'What are governments doing to improve transport?', 'type' => 'fill_blank', 'answer' => 'investing money'],
                            ['text' => 'True or False: Public transport use has decreased recently.', 'type' => 'true_false', 'answer' => 'false', 'options' => ['true', 'false']],
                        ],
                    ],
                ],
                'listening' => [
                    [
                        'title'      => 'Band 6.5 - Booking a Hotel Room',
                        'audio_path' => 'listening/band6_hotel_booking.mp3',
                        'transcript' => 'Receptionist: Good morning, City Hotel. How can I help you? Guest: Hello, I would like to book a room for two nights. Receptionist: Of course. What dates would you like? Guest: From the 15th to the 17th of March. Receptionist: We have a standard room for £75 per night or a deluxe room for £110 per night. Guest: I will take the standard room please. Receptionist: Great. Can I take your name? Guest: It is James Wilson.',
                        'duration'   => 150,
                        'questions'  => [
                            ['text' => 'How many nights does the guest want to stay?', 'type' => 'fill_blank', 'answer' => 'two nights'],
                            ['text' => 'What is the check-in date?', 'type' => 'fill_blank', 'answer' => '15th of March'],
                            ['text' => 'How much is the standard room per night?', 'type' => 'multiple_choice', 'answer' => '£75', 'options' => ['£55', '£75', '£95', '£110']],
                            ['text' => 'Which room does the guest choose?', 'type' => 'multiple_choice', 'answer' => 'standard room', 'options' => ['deluxe room', 'standard room', 'suite', 'family room']],
                            ['text' => 'What is the guest\'s name?', 'type' => 'fill_blank', 'answer' => 'James Wilson'],
                        ],
                    ],
                ],
            ],

            'band7' => [
                'reading' => [
                    [
                        'title'   => 'Band 7 - The Psychology of Decision Making',
                        'content' => "Decision making is a complex cognitive process influenced by both rational analysis and emotional responses. Psychologists have identified two primary systems of thinking: System 1, which operates automatically and intuitively, and System 2, which involves deliberate, analytical reasoning. Research by Daniel Kahneman demonstrates that people frequently rely on cognitive shortcuts, known as heuristics, which can lead to systematic errors called cognitive biases. For instance, the availability heuristic causes individuals to overestimate the likelihood of events that come easily to mind. Understanding these biases is essential for improving decision quality in professional and personal contexts.",
                        'questions' => [
                            ['text' => 'What are the two primary systems of thinking?', 'type' => 'fill_blank', 'answer' => 'System 1 and System 2'],
                            ['text' => 'Which system involves deliberate analytical reasoning?', 'type' => 'multiple_choice', 'answer' => 'System 2', 'options' => ['System 1', 'System 2', 'System 3', 'System 4']],
                            ['text' => 'What are cognitive shortcuts called?', 'type' => 'fill_blank', 'answer' => 'heuristics'],
                            ['text' => 'What does the availability heuristic cause people to do?', 'type' => 'multiple_choice', 'answer' => 'overestimate the likelihood of easily recalled events', 'options' => ['underestimate risks', 'overestimate the likelihood of easily recalled events', 'ignore emotional responses', 'rely on System 2 thinking']],
                            ['text' => 'Who conducted research on cognitive biases mentioned in the text?', 'type' => 'fill_blank', 'answer' => 'Daniel Kahneman'],
                        ],
                    ],
                    [
                        'title'   => 'Band 7 - Urbanisation and Its Consequences',
                        'content' => "Urbanisation, the process by which rural populations migrate to cities, has accelerated dramatically over the past century. Currently, more than half of the world's population lives in urban areas, a figure projected to reach 68% by 2050. While cities offer economic opportunities and improved access to services, rapid urbanisation creates significant challenges. Infrastructure strain, housing shortages, and increased inequality are common consequences. Informal settlements, often called slums, house approximately one billion people globally. Sustainable urban planning, incorporating green spaces, efficient public transport, and affordable housing, is increasingly recognised as essential for managing urban growth effectively.",
                        'questions' => [
                            ['text' => 'What percentage of the world\'s population currently lives in urban areas?', 'type' => 'fill_blank', 'answer' => 'more than half'],
                            ['text' => 'What is the projected urban population percentage by 2050?', 'type' => 'multiple_choice', 'answer' => '68%', 'options' => ['55%', '60%', '68%', '75%']],
                            ['text' => 'How many people live in informal settlements globally?', 'type' => 'fill_blank', 'answer' => 'approximately one billion'],
                            ['text' => 'Name one challenge of rapid urbanisation.', 'type' => 'multiple_choice', 'answer' => 'housing shortages', 'options' => ['reduced crime rates', 'housing shortages', 'lower unemployment', 'improved air quality']],
                            ['text' => 'What does sustainable urban planning incorporate?', 'type' => 'fill_blank', 'answer' => 'green spaces, efficient public transport, and affordable housing'],
                        ],
                    ],
                ],
                'listening' => [
                    [
                        'title'      => 'Band 7 - University Seminar on Climate Policy',
                        'audio_path' => 'listening/band7_climate_seminar.mp3',
                        'transcript' => 'Lecturer: Today we examine the Paris Agreement, adopted in 2015, which commits signatory nations to limiting global temperature rise to 1.5 degrees Celsius above pre-industrial levels. Student A: What mechanisms enforce compliance? Lecturer: The agreement relies on nationally determined contributions, or NDCs, which countries submit every five years. Crucially, these are voluntary commitments with no binding penalties for non-compliance. Student B: Has there been measurable progress? Lecturer: Current NDCs, if fully implemented, would limit warming to approximately 2.7 degrees — insufficient to meet the 1.5 degree target.',
                        'duration'   => 200,
                        'questions'  => [
                            ['text' => 'When was the Paris Agreement adopted?', 'type' => 'fill_blank', 'answer' => '2015'],
                            ['text' => 'What temperature rise limit does the agreement target?', 'type' => 'multiple_choice', 'answer' => '1.5 degrees Celsius', 'options' => ['1.0 degrees Celsius', '1.5 degrees Celsius', '2.0 degrees Celsius', '2.5 degrees Celsius']],
                            ['text' => 'What are NDCs?', 'type' => 'fill_blank', 'answer' => 'nationally determined contributions'],
                            ['text' => 'How often do countries submit NDCs?', 'type' => 'multiple_choice', 'answer' => 'every five years', 'options' => ['every year', 'every two years', 'every five years', 'every ten years']],
                            ['text' => 'What warming level would current NDCs produce if fully implemented?', 'type' => 'fill_blank', 'answer' => 'approximately 2.7 degrees'],
                        ],
                    ],
                ],
            ],

            'band8' => [
                'reading' => [
                    [
                        'title'   => 'Band 8 - Neuroplasticity and Cognitive Rehabilitation',
                        'content' => "Neuroplasticity, the brain's capacity to reorganise its synaptic connections in response to experience, injury, or environmental stimuli, has fundamentally transformed our understanding of cognitive rehabilitation. Contrary to the long-held assumption that neural architecture becomes immutable after early childhood, contemporary neuroscience has demonstrated that the adult brain retains considerable plasticity throughout the lifespan. This has profound implications for the treatment of acquired brain injuries, stroke rehabilitation, and neurodegenerative conditions. Constraint-induced movement therapy, for instance, exploits neuroplastic mechanisms by restricting the unaffected limb, thereby compelling the neural circuits associated with the impaired limb to reorganise and strengthen. Longitudinal studies indicate that intensive, task-specific training can induce measurable cortical remapping within weeks.",
                        'questions' => [
                            ['text' => 'What does neuroplasticity refer to?', 'type' => 'fill_blank', 'answer' => "the brain's capacity to reorganise its synaptic connections"],
                            ['text' => 'What assumption did contemporary neuroscience overturn?', 'type' => 'multiple_choice', 'answer' => 'that neural architecture becomes immutable after early childhood', 'options' => ['that the brain cannot recover from injury', 'that neural architecture becomes immutable after early childhood', 'that plasticity only occurs during sleep', 'that rehabilitation is ineffective for adults']],
                            ['text' => 'How does constraint-induced movement therapy work?', 'type' => 'fill_blank', 'answer' => 'by restricting the unaffected limb to compel neural circuits to reorganise'],
                            ['text' => 'What can intensive task-specific training induce?', 'type' => 'multiple_choice', 'answer' => 'measurable cortical remapping', 'options' => ['permanent memory loss', 'measurable cortical remapping', 'reduced synaptic density', 'accelerated neurodegeneration']],
                            ['text' => 'Within what timeframe can cortical remapping occur?', 'type' => 'fill_blank', 'answer' => 'within weeks'],
                        ],
                    ],
                    [
                        'title'   => 'Band 8 - The Economics of Intellectual Property',
                        'content' => "Intellectual property rights (IPR) represent a fundamental tension between incentivising innovation and ensuring broad access to knowledge. Patent systems grant inventors temporary monopolies — typically 20 years — ostensibly to recoup research and development expenditure and generate returns sufficient to motivate further innovation. Critics contend, however, that patent thickets, wherein overlapping intellectual property claims obstruct subsequent innovation, and evergreening strategies, whereby pharmaceutical companies make minor modifications to extend patent protection, undermine the system's original intent. Empirical evidence on the relationship between patent strength and innovation rates remains contested, with some economists arguing that robust IPR regimes disproportionately benefit incumbent firms at the expense of new market entrants and consumers in developing economies.",
                        'questions' => [
                            ['text' => 'How long do patents typically last?', 'type' => 'fill_blank', 'answer' => '20 years'],
                            ['text' => 'What are patent thickets?', 'type' => 'multiple_choice', 'answer' => 'overlapping intellectual property claims that obstruct innovation', 'options' => ['government-issued research grants', 'overlapping intellectual property claims that obstruct innovation', 'international trade agreements on patents', 'databases of expired patents']],
                            ['text' => 'What is evergreening?', 'type' => 'fill_blank', 'answer' => 'making minor modifications to extend patent protection'],
                            ['text' => 'Who do robust IPR regimes disproportionately benefit according to some economists?', 'type' => 'multiple_choice', 'answer' => 'incumbent firms', 'options' => ['developing economies', 'incumbent firms', 'academic researchers', 'government agencies']],
                            ['text' => 'What remains contested according to the text?', 'type' => 'fill_blank', 'answer' => 'the relationship between patent strength and innovation rates'],
                        ],
                    ],
                ],
                'listening' => [
                    [
                        'title'      => 'Band 8 - Academic Debate on Artificial General Intelligence',
                        'audio_path' => 'listening/band8_agi_debate.mp3',
                        'transcript' => 'Moderator: Our panel today addresses the timeline and implications of artificial general intelligence. Professor Chen, you argue AGI is imminent. Chen: The convergence of transformer architectures, reinforcement learning from human feedback, and exponentially increasing compute suggests we may achieve human-level reasoning within a decade. However, I distinguish between narrow superintelligence and genuine general cognition. Dr. Patel: I remain sceptical. Current systems exhibit sophisticated pattern matching but lack genuine understanding, causal reasoning, and embodied cognition. The benchmark problem — where systems excel at tests but fail at novel real-world tasks — suggests we are further from AGI than proponents claim.',
                        'duration'   => 240,
                        'questions'  => [
                            ['text' => 'What three factors does Professor Chen cite as converging?', 'type' => 'fill_blank', 'answer' => 'transformer architectures, reinforcement learning from human feedback, and increasing compute'],
                            ['text' => 'Within what timeframe does Professor Chen suggest AGI may emerge?', 'type' => 'multiple_choice', 'answer' => 'within a decade', 'options' => ['within two years', 'within five years', 'within a decade', 'within fifty years']],
                            ['text' => 'What does Dr. Patel say current systems lack?', 'type' => 'fill_blank', 'answer' => 'genuine understanding, causal reasoning, and embodied cognition'],
                            ['text' => 'What is the benchmark problem?', 'type' => 'multiple_choice', 'answer' => 'systems excel at tests but fail at novel real-world tasks', 'options' => ['systems cannot pass standardised tests', 'systems excel at tests but fail at novel real-world tasks', 'benchmarks are too easy for current AI', 'there are no agreed benchmarks for AGI']],
                            ['text' => 'What distinction does Professor Chen draw?', 'type' => 'fill_blank', 'answer' => 'between narrow superintelligence and genuine general cognition'],
                        ],
                    ],
                ],
            ],

            'band9' => [
                'reading' => [
                    [
                        'title'   => 'Band 9 - Epistemic Injustice and the Philosophy of Testimony',
                        'content' => "Miranda Fricker's seminal work on epistemic injustice identifies two primary forms of harm inflicted upon individuals in their capacity as knowers: testimonial injustice, wherein a speaker receives a credibility deficit attributable to identity prejudice, and hermeneutical injustice, wherein a gap in collective interpretive resources places someone at an unfair disadvantage when attempting to make sense of their own social experience. The latter form is particularly insidious because it operates structurally rather than through individual acts of discrimination; marginalised groups may lack the conceptual vocabulary to articulate experiences of oppression, rendering those experiences epistemically invisible. Fricker's framework has been critiqued for its individualistic focus, with subsequent scholars arguing that structural epistemic injustice requires systemic rather than merely interpersonal remediation. The concept has nonetheless proved generative across disciplines, informing debates in medical ethics, legal theory, and the philosophy of science.",
                        'questions' => [
                            ['text' => 'What is testimonial injustice?', 'type' => 'fill_blank', 'answer' => 'when a speaker receives a credibility deficit attributable to identity prejudice'],
                            ['text' => 'Why is hermeneutical injustice described as particularly insidious?', 'type' => 'multiple_choice', 'answer' => 'it operates structurally rather than through individual acts of discrimination', 'options' => ['it is more common than testimonial injustice', 'it operates structurally rather than through individual acts of discrimination', 'it only affects academic communities', 'it cannot be remedied through legislation']],
                            ['text' => 'What may marginalised groups lack according to the text?', 'type' => 'fill_blank', 'answer' => 'the conceptual vocabulary to articulate experiences of oppression'],
                            ['text' => 'What critique has been levelled at Fricker\'s framework?', 'type' => 'multiple_choice', 'answer' => 'its individualistic focus', 'options' => ['its overemphasis on structural factors', 'its individualistic focus', 'its failure to address testimonial injustice', 'its reliance on empirical rather than philosophical methods']],
                            ['text' => 'In which fields has the concept proved generative?', 'type' => 'fill_blank', 'answer' => 'medical ethics, legal theory, and the philosophy of science'],
                        ],
                    ],
                    [
                        'title'   => 'Band 9 - Quantum Decoherence and the Measurement Problem',
                        'content' => "The measurement problem in quantum mechanics — the question of how and why quantum superpositions collapse into definite classical outcomes upon observation — remains one of the most philosophically contentious issues in contemporary physics. Decoherence theory, developed principally by Wojciech Zurek, proposes that quantum systems inevitably interact with their environments, causing the rapid suppression of interference terms in the density matrix and the apparent emergence of classical behaviour. Crucially, decoherence does not resolve the measurement problem per se; it explains why superpositions appear to collapse without specifying which outcome is actualised. The many-worlds interpretation, by contrast, denies collapse altogether, positing that all outcomes are realised in branching parallel universes. Critics of many-worlds invoke Occam's razor against its ontological profligacy, while proponents argue it is the most parsimonious interpretation consistent with the unitary formalism of quantum mechanics.",
                        'questions' => [
                            ['text' => 'Who principally developed decoherence theory?', 'type' => 'fill_blank', 'answer' => 'Wojciech Zurek'],
                            ['text' => 'What does decoherence theory propose?', 'type' => 'multiple_choice', 'answer' => 'quantum systems interact with their environments causing suppression of interference terms', 'options' => ['quantum systems are isolated from their environments', 'quantum systems interact with their environments causing suppression of interference terms', 'observation creates new quantum states', 'superpositions are mathematically impossible']],
                            ['text' => 'What does decoherence NOT resolve?', 'type' => 'fill_blank', 'answer' => 'which outcome is actualised'],
                            ['text' => 'What does the many-worlds interpretation posit?', 'type' => 'multiple_choice', 'answer' => 'all outcomes are realised in branching parallel universes', 'options' => ['only one universe exists', 'all outcomes are realised in branching parallel universes', 'quantum collapse is instantaneous', 'observers determine which outcome occurs']],
                            ['text' => 'What do critics of many-worlds invoke against it?', 'type' => 'fill_blank', 'answer' => "Occam's razor against its ontological profligacy"],
                        ],
                    ],
                ],
                'listening' => [
                    [
                        'title'      => 'Band 9 - Symposium on Post-Colonial Legal Theory',
                        'audio_path' => 'listening/band9_postcolonial_law.mp3',
                        'transcript' => 'Chair: Our symposium examines the extent to which international law perpetuates colonial hierarchies. Professor Anghie, your work on the dynamic of difference is foundational here. Anghie: The civilising mission that justified colonial intervention has been transmuted into the development discourse of contemporary international institutions. The conditionality attached to IMF structural adjustment programmes, for instance, replicates the logic of trusteeship — the assumption that certain states require external governance to achieve modernity. Dr. Gathii: I would add that the very architecture of international investment law, with its investor-state dispute settlement mechanisms, systematically privileges transnational capital over the regulatory sovereignty of postcolonial states, entrenching asymmetries that predate formal decolonisation.',
                        'duration'   => 260,
                        'questions'  => [
                            ['text' => 'What has the civilising mission been transmuted into according to Professor Anghie?', 'type' => 'fill_blank', 'answer' => 'the development discourse of contemporary international institutions'],
                            ['text' => 'What logic do IMF structural adjustment programmes replicate?', 'type' => 'multiple_choice', 'answer' => 'the logic of trusteeship', 'options' => ['the logic of sovereignty', 'the logic of trusteeship', 'the logic of free trade', 'the logic of self-determination']],
                            ['text' => 'What does investor-state dispute settlement privilege according to Dr. Gathii?', 'type' => 'fill_blank', 'answer' => 'transnational capital over the regulatory sovereignty of postcolonial states'],
                            ['text' => 'When did the asymmetries Dr. Gathii describes originate?', 'type' => 'multiple_choice', 'answer' => 'before formal decolonisation', 'options' => ['after formal decolonisation', 'during the Cold War', 'before formal decolonisation', 'with the establishment of the IMF']],
                            ['text' => 'What is the central concern of the symposium?', 'type' => 'fill_blank', 'answer' => 'whether international law perpetuates colonial hierarchies'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
