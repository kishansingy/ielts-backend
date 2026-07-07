<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MockTest;
use App\Models\MockTestSection;
use App\Models\ReadingPassage;
use App\Models\ListeningExercise;
use App\Models\WritingTask;
use App\Models\SpeakingPrompt;
use Illuminate\Support\Facades\DB;

class ProductionMockTestsSeeder extends Seeder
{
    private $bandLevels = ['band6', 'band7', 'band8', 'band9'];
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create a system admin user for content creation
        $adminUser = \App\Models\User::firstOrCreate(
            ['email' => 'admin@ielts.com'],
            [
                'name' => 'System Admin',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );
        
        DB::transaction(function () use ($adminUser) {
            foreach ($this->bandLevels as $bandLevel) {
                $this->createMockTestsForBand($bandLevel, $adminUser);
            }
        });
        
        $this->command->info('Production Mock Tests seeded successfully!');
    }

    /**
     * Create 20 mock tests for each band level
     */
    private function createMockTestsForBand($bandLevel, $adminUser)
    {
        $this->command->info("Creating mock tests for {$bandLevel}...");
        
        for ($i = 1; $i <= 20; $i++) {
            $mockTest = MockTest::create([
                'title' => "IELTS Mock Test {$i} - " . strtoupper($bandLevel),
                'description' => "Complete IELTS practice test {$i} for {$bandLevel} level. Includes Reading, Listening, Writing, and Speaking sections.",
                'band_level' => $bandLevel,
                'duration_minutes' => 180, // 3 hours total
                'is_active' => true,
                'available_from' => now(),
                'available_until' => null,
            ]);

            // Add Reading Section
            $this->addReadingSection($mockTest, $bandLevel, $i, $adminUser);
            
            // Add Listening Section
            $this->addListeningSection($mockTest, $bandLevel, $i, $adminUser);
            
            // Add Writing Section
            $this->addWritingSection($mockTest, $bandLevel, $i, $adminUser);
            
            // Add Speaking Section
            $this->addSpeakingSection($mockTest, $bandLevel, $i, $adminUser);
        }
    }

    /**
     * Add Reading section to mock test
     * Only uses passages not already assigned to another mock test of the same band
     */
    private function addReadingSection($mockTest, $bandLevel, $testNumber, $adminUser)
    {
        // Get IDs already used in any mock test for this band
        $usedIds = MockTestSection::where('module_type', 'reading')
            ->whereHas('mockTest', fn($q) => $q->where('band_level', $bandLevel))
            ->pluck('content_id')
            ->unique()
            ->toArray();

        // Get unused passages first
        $passages = ReadingPassage::where('band_level', $bandLevel)
            ->whereNotIn('id', $usedIds)
            ->with('questions')
            ->take(3)
            ->get();

        // If not enough unused passages, create new ones
        for ($i = $passages->count(); $i < 3; $i++) {
            $passageNumber = $i + 1;
            $passage = ReadingPassage::create([
                'title' => $this->getReadingTitle($testNumber, $passageNumber),
                'content' => $this->getReadingContent($bandLevel, $testNumber, $passageNumber),
                'difficulty_level' => $this->getDifficultyLevel($bandLevel),
                'band_level' => $bandLevel,
                'time_limit' => 20,
                'created_by' => $adminUser->id,
            ]);
            $this->createQuestionsForPassage($passage, $bandLevel, $passageNumber);
            $passages->push($passage);
        }

        $order = 1;
        foreach ($passages as $passage) {
            MockTestSection::create([
                'mock_test_id' => $mockTest->id,
                'module_type' => 'reading',
                'content_id' => $passage->id,
                'content_type' => ReadingPassage::class,
                'order' => $order++,
                'duration_minutes' => 20,
            ]);
        }
    }

    /**
     * Add Listening section to mock test
     * Only uses exercises not already assigned to another mock test of the same band
     */
    private function addListeningSection($mockTest, $bandLevel, $testNumber, $adminUser)
    {
        $usedIds = MockTestSection::where('module_type', 'listening')
            ->whereHas('mockTest', fn($q) => $q->where('band_level', $bandLevel))
            ->pluck('content_id')
            ->unique()
            ->toArray();

        $exercises = ListeningExercise::where('band_level', $bandLevel)
            ->whereNotIn('id', $usedIds)
            ->with('questions')
            ->take(1)
            ->get();

        if ($exercises->isEmpty()) {
            $exercise = ListeningExercise::create([
                'title' => $this->getListeningTitle($bandLevel, $testNumber),
                'audio_file_path' => "listening/mock_test_{$testNumber}_band_{$bandLevel}.mp3",
                'transcript' => $this->getListeningTranscript($bandLevel, $testNumber, 1),
                'duration' => 180,
                'difficulty_level' => $this->getDifficultyLevel($bandLevel),
                'band_level' => $bandLevel,
                'created_by' => $adminUser->id,
            ]);
            $this->createQuestionsForListening($exercise, $testNumber);
            $exercises->push($exercise);
        }

        $order = 4;
        foreach ($exercises as $exercise) {
            MockTestSection::create([
                'mock_test_id' => $mockTest->id,
                'module_type' => 'listening',
                'content_id' => $exercise->id,
                'content_type' => ListeningExercise::class,
                'order' => $order++,
                'duration_minutes' => 30,
            ]);
        }
    }

    /**
     * Add Writing section to mock test
     * Reuses existing tasks by cycling, but never duplicates within the same mock test
     */
    private function addWritingSection($mockTest, $bandLevel, $testNumber, $adminUser)
    {
        // Try to reuse existing task1 not used in this same mock test
        $usedInThisTest = MockTestSection::where('mock_test_id', $mockTest->id)
            ->where('module_type', 'writing')
            ->pluck('content_id')
            ->toArray();

        $task1 = WritingTask::where('band_level', $bandLevel)
            ->where('task_type', 'task1')
            ->whereNotIn('id', $usedInThisTest)
            ->first();

        if (!$task1) {
            $task1 = WritingTask::create([
                'title' => "Writing Task 1 - {$bandLevel} Test {$testNumber}",
                'task_type' => 'task1',
                'prompt' => $this->getWritingTask1Prompt($testNumber),
                'instructions' => 'Summarize the information by selecting and reporting the main features, and make comparisons where relevant. Write at least 150 words.',
                'time_limit' => 20,
                'word_limit' => 150,
                'band_level' => $bandLevel,
                'created_by' => $adminUser->id,
            ]);
        }

        MockTestSection::create([
            'mock_test_id' => $mockTest->id,
            'module_type' => 'writing',
            'content_id' => $task1->id,
            'content_type' => WritingTask::class,
            'order' => 8,
            'duration_minutes' => 20,
        ]);

        $usedInThisTest[] = $task1->id;

        $task2 = WritingTask::where('band_level', $bandLevel)
            ->where('task_type', 'task2')
            ->whereNotIn('id', $usedInThisTest)
            ->first();

        if (!$task2) {
            $task2 = WritingTask::create([
                'title' => "Writing Task 2 - {$bandLevel} Test {$testNumber}",
                'task_type' => 'task2',
                'prompt' => $this->getWritingTask2Prompt($testNumber),
                'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.',
                'time_limit' => 40,
                'word_limit' => 250,
                'band_level' => $bandLevel,
                'created_by' => $adminUser->id,
            ]);
        }

        MockTestSection::create([
            'mock_test_id' => $mockTest->id,
            'module_type' => 'writing',
            'content_id' => $task2->id,
            'content_type' => WritingTask::class,
            'order' => 9,
            'duration_minutes' => 40,
        ]);
    }

    /**
     * Add Speaking section to mock test
     * Reuses existing prompts not already used in this same mock test
     */
    private function addSpeakingSection($mockTest, $bandLevel, $testNumber, $adminUser)
    {
        $usedInThisTest = [];
        $order = 10;

        $parts = [
            ['part' => 1, 'prep' => 0,  'response' => 240, 'prompt_fn' => 'getSpeakingPart1Prompt'],
            ['part' => 2, 'prep' => 60, 'response' => 120, 'prompt_fn' => 'getSpeakingPart2Prompt'],
            ['part' => 3, 'prep' => 0,  'response' => 300, 'prompt_fn' => 'getSpeakingPart3Prompt'],
        ];

        foreach ($parts as $partData) {
            $prompt = SpeakingPrompt::where('band_level', $bandLevel)
                ->whereNotIn('id', $usedInThisTest)
                ->where('preparation_time', $partData['prep'])
                ->first();

            if (!$prompt) {
                $prompt = SpeakingPrompt::create([
                    'title' => "Speaking Part {$partData['part']} - {$bandLevel} Test {$testNumber}",
                    'prompt_text' => $this->{$partData['prompt_fn']}($testNumber),
                    'preparation_time' => $partData['prep'],
                    'response_time' => $partData['response'],
                    'difficulty_level' => $this->getDifficultyLevel($bandLevel),
                    'band_level' => $bandLevel,
                    'created_by' => $adminUser->id,
                ]);
            }

            $usedInThisTest[] = $prompt->id;

            MockTestSection::create([
                'mock_test_id' => $mockTest->id,
                'module_type' => 'speaking',
                'content_id' => $prompt->id,
                'content_type' => SpeakingPrompt::class,
                'order' => $order++,
                'duration_minutes' => (int)(($partData['prep'] + $partData['response']) / 60),
            ]);
        }
    }

    /**
     * Get difficulty level based on band
     */
    private function getDifficultyLevel($bandLevel)
    {
        return match ($bandLevel) {
            'band6' => 'beginner',
            'band7' => 'intermediate',
            'band8', 'band9' => 'advanced',
            default => 'intermediate',
        };
    }

    /**
     * Get a meaningful title for a reading passage
     */
    private function getReadingTitle($testNumber, $passageNumber)
    {
        $titles = [
            1 => [
                'The Rise of Artificial Intelligence',
                'A History of Computing',
                'The New Space Age',
            ],
            2 => [
                'Biodiversity and Ecosystem Services',
                'The Threatened Oceans',
                'Climate Change: Causes and Consequences',
            ],
            3 => [
                'Social Media and Mental Health',
                'The Psychology of Decision-Making',
                'Urban Planning for the Future',
            ],
        ];

        $titleIndex = ($testNumber - 1) % 3;
        return $titles[$passageNumber][$titleIndex] ?? "Reading Passage {$passageNumber} - Test {$testNumber}";
    }

    /**
     * Generate reading content for mock tests
     * Returns real, unique passage content per topic
     */
    private function getReadingContent($bandLevel, $testNumber, $passageNumber)
    {
        // 3 distinct full passages per topic slot
        $passages = [
            1 => [ // Passage 1 topics - Technology / Science
                "Artificial intelligence (AI) has emerged as one of the most transformative technologies of the twenty-first century. Unlike earlier computing systems that followed rigid, pre-programmed rules, modern AI systems learn from data, identifying patterns and making decisions with minimal human intervention. Machine learning, a subset of AI, has enabled computers to perform tasks once thought to require human intelligence, such as recognising speech, translating languages, and diagnosing diseases from medical images.\n\nThe healthcare sector has been among the first to feel the impact. AI-powered diagnostic tools can now analyse thousands of X-rays or MRI scans in the time it takes a radiologist to review a handful, and with comparable accuracy. In oncology, algorithms trained on millions of patient records can predict which tumours are likely to respond to specific treatments, enabling more personalised care. Hospitals in several countries have begun deploying AI triage systems that assess incoming patients and prioritise those most at risk.\n\nIn finance, algorithmic trading systems execute millions of transactions per second, responding to market signals far faster than any human trader. Fraud detection models scan credit card transactions in real time, flagging anomalies that would take human analysts days to identify. Loan approval processes that once required weeks of manual review can now be completed in minutes, broadening access to credit for underserved populations.\n\nDespite these advances, AI raises profound ethical questions. Algorithmic bias — where systems trained on historical data perpetuate existing inequalities — has been documented in hiring tools, criminal sentencing software, and facial recognition systems. Researchers and policymakers are grappling with how to ensure that AI systems are transparent, accountable, and fair. The European Union has proposed comprehensive AI regulations that would classify systems by risk level and impose strict requirements on high-risk applications.\n\nThe labour market implications are equally contested. Some economists predict that automation will displace millions of workers in manufacturing, logistics, and administrative roles. Others argue that, as with previous technological revolutions, AI will ultimately create more jobs than it destroys by generating new industries and increasing overall productivity. The truth likely lies somewhere in between, with significant disruption concentrated among workers with routine, codifiable skills and new opportunities emerging for those who can collaborate effectively with intelligent systems.\n\nLooking ahead, researchers are pursuing artificial general intelligence (AGI) — systems capable of performing any intellectual task a human can. While most experts believe AGI remains decades away, the pace of progress has accelerated dramatically. Investments in AI research by governments and technology companies reached record levels in recent years, and breakthroughs in areas such as large language models and reinforcement learning have repeatedly surprised even optimistic forecasters.",

                "The history of computing stretches back further than most people realise. Long before the first electronic computers appeared in the 1940s, mathematicians and engineers were designing mechanical devices capable of performing calculations. Charles Babbage's Analytical Engine, conceived in the 1830s, contained many of the conceptual elements of a modern computer, including a processing unit, memory, and the ability to be programmed using punched cards. Although the machine was never completed in Babbage's lifetime, his collaborator Ada Lovelace wrote what is widely regarded as the first computer program.\n\nThe electronic era began in earnest during the Second World War, when governments on both sides of the conflict invested heavily in computing technology for code-breaking and ballistic calculations. The British Colossus, built at Bletchley Park, helped decipher German military communications, while the American ENIAC, completed in 1945, could perform thousands of calculations per second — a speed unimaginable with mechanical devices.\n\nThe invention of the transistor in 1947 at Bell Laboratories marked the beginning of a new phase. Transistors replaced bulky, unreliable vacuum tubes, making computers smaller, cheaper, and more energy-efficient. The subsequent development of integrated circuits, which packed thousands of transistors onto a single silicon chip, triggered an exponential increase in computing power that has continued for more than half a century. Gordon Moore, co-founder of Intel, observed in 1965 that the number of transistors on a chip was doubling approximately every two years — a trend that became known as Moore's Law.\n\nPersonal computing arrived in the 1970s and 1980s, transforming computers from specialised scientific instruments into everyday tools. The Apple II, the IBM PC, and later the Macintosh brought computing into homes and offices around the world. The development of graphical user interfaces, which replaced cryptic text commands with intuitive icons and menus, made computers accessible to people without technical training.\n\nThe internet, originally a network connecting universities and research institutions, became a global communications infrastructure in the 1990s. The World Wide Web, invented by Tim Berners-Lee in 1989, provided a user-friendly interface for navigating the internet's vast resources. Within a decade, e-commerce, online banking, and digital communication had fundamentally altered how people worked, shopped, and socialised.\n\nToday, computing is ubiquitous. Smartphones carry more processing power than the supercomputers of the 1980s. Cloud computing allows individuals and organisations to access vast computational resources on demand. Quantum computing, still in its early stages, promises to solve problems that are intractable for classical computers, with potential applications in drug discovery, materials science, and cryptography.",

                "Space exploration has captivated human imagination since the earliest astronomers turned their eyes to the night sky. The modern era of space exploration began in earnest in 1957, when the Soviet Union launched Sputnik, the world's first artificial satellite. The beeping signal it transmitted as it orbited Earth sent shockwaves through the Western world and ignited the Space Race — a decade-long competition between the superpowers that culminated in the Apollo 11 mission of 1969, when Neil Armstrong and Buzz Aldrin became the first humans to walk on the Moon.\n\nThe scientific returns from space exploration have been immense. Satellites have revolutionised weather forecasting, telecommunications, navigation, and environmental monitoring. The Hubble Space Telescope, launched in 1990, has provided breathtaking images of distant galaxies and helped astronomers determine the age and expansion rate of the universe. Robotic missions to Mars have revealed evidence of ancient river systems and subsurface water ice, raising the tantalising possibility that the planet may once have harboured microbial life.\n\nIn recent years, the space sector has undergone a dramatic transformation. Historically dominated by government agencies such as NASA, the European Space Agency, and Roscosmos, the industry has attracted a wave of private investment. Companies like SpaceX, Blue Origin, and Virgin Galactic have developed reusable rockets that dramatically reduce the cost of reaching orbit. SpaceX's Falcon 9 rocket, which can land its first stage booster for reuse, has cut launch costs by an order of magnitude compared with expendable rockets.\n\nThis commercialisation has opened new possibilities. Satellite constellations like SpaceX's Starlink aim to provide high-speed internet access to remote and underserved areas around the world. Space tourism, once the preserve of science fiction, is becoming a reality, with several companies offering suborbital flights to paying passengers. Asteroid mining, which could yield vast quantities of rare metals and water ice, is being seriously explored by a number of start-ups.\n\nHuman missions beyond low Earth orbit are once again on the agenda. NASA's Artemis programme aims to return astronauts to the Moon by the mid-2020s, with the long-term goal of establishing a sustainable lunar presence. Mars remains the ultimate destination for human exploration, with both NASA and SpaceX developing plans for crewed missions in the 2030s or 2040s. Such missions would require solving formidable technical challenges, including protecting astronauts from cosmic radiation, providing life support for journeys lasting months, and developing reliable systems for landing and ascending from the Martian surface.",
            ],
            2 => [ // Passage 2 topics - Environment / Nature
                "Biodiversity — the variety of life on Earth — underpins the functioning of every ecosystem on the planet. From the microscopic bacteria that decompose organic matter in the soil to the apex predators that regulate prey populations, each species plays a role in maintaining the balance of natural systems. Scientists estimate that Earth is home to between eight and ten million species, of which fewer than two million have been formally described and named. The vast majority remain unknown to science.\n\nThe services that biodiversity provides to humanity are both enormous and largely invisible. Healthy forests regulate the water cycle, absorbing rainfall and releasing it gradually into rivers and aquifers. Wetlands filter pollutants from water and buffer coastal communities against storm surges. Pollinators — bees, butterflies, birds, and bats — are essential for the reproduction of approximately three-quarters of the world's flowering plants, including many of the crops that feed humanity. The economic value of pollination services alone has been estimated at hundreds of billions of dollars annually.\n\nDespite its importance, biodiversity is declining at an alarming rate. The current rate of species extinction is estimated to be between one hundred and one thousand times higher than the natural background rate, leading many scientists to describe the present era as the sixth mass extinction event in Earth's history. The primary drivers are habitat destruction, overexploitation, invasive species, pollution, and climate change. Tropical rainforests, which harbour more than half of all terrestrial species, are being cleared for agriculture and logging at a rate of millions of hectares per year.\n\nConservation efforts have achieved notable successes. The bald eagle, once on the brink of extinction due to hunting and the pesticide DDT, has recovered to healthy population levels following legal protection and habitat restoration. The mountain gorilla, one of the world's most endangered primates, has seen its population grow from fewer than 300 individuals in the 1980s to more than 1,000 today, thanks to intensive conservation programmes in Uganda, Rwanda, and the Democratic Republic of Congo.\n\nHowever, conservation resources remain woefully inadequate relative to the scale of the challenge. Protected areas cover approximately fifteen percent of the Earth's land surface, but many are poorly managed and lack sufficient funding for effective enforcement. The Convention on Biological Diversity, signed by 196 countries, has set ambitious targets for expanding protected areas and reducing the drivers of biodiversity loss, but progress towards these goals has been slow.\n\nAddressing the biodiversity crisis will require fundamental changes in how humanity produces food, manages land, and values nature. Sustainable agriculture practices that reduce the use of pesticides and preserve habitat corridors can help maintain biodiversity in agricultural landscapes. Payment for ecosystem services — compensating landowners for maintaining forests and wetlands — can make conservation economically attractive. Ultimately, protecting biodiversity requires recognising that human well-being depends on the health of the natural systems that sustain all life on Earth.",

                "The world's oceans cover more than seventy percent of the Earth's surface and play a fundamental role in regulating the planet's climate. They absorb approximately a quarter of the carbon dioxide emitted by human activities and generate more than half of the oxygen in the atmosphere, primarily through the photosynthesis of microscopic marine plants called phytoplankton. Ocean currents distribute heat around the globe, moderating temperatures in coastal regions and driving weather patterns far inland.\n\nDespite their importance, the oceans face an unprecedented array of threats. Climate change is warming ocean waters and causing them to become more acidic as they absorb carbon dioxide. Ocean acidification threatens the ability of corals, molluscs, and other marine organisms to build their calcium carbonate shells and skeletons. Coral reefs, which support approximately a quarter of all marine species despite covering less than one percent of the ocean floor, have experienced widespread bleaching events as water temperatures rise.\n\nPlastic pollution has emerged as one of the most visible environmental problems of the modern era. An estimated eight million tonnes of plastic enter the oceans each year, where it breaks down into microplastics that are ingested by marine animals throughout the food chain. Studies have found microplastics in the stomachs of fish, seabirds, and marine mammals, as well as in the tissues of humans who consume seafood. The Great Pacific Garbage Patch, a vast accumulation of plastic debris in the North Pacific Ocean, has become a symbol of the scale of the problem.\n\nOverfishing has depleted fish stocks around the world. The United Nations Food and Agriculture Organization estimates that more than a third of the world's fish stocks are being harvested at biologically unsustainable levels. Industrial fishing fleets equipped with sonar, GPS, and enormous nets can locate and capture fish with an efficiency that leaves populations little chance to recover. Bycatch — the unintended capture of non-target species, including dolphins, sea turtles, and seabirds — causes additional harm to marine ecosystems.\n\nEfforts to protect the oceans have gained momentum in recent years. Marine protected areas, which restrict or prohibit fishing and other extractive activities, have been established in many parts of the world. Research has shown that well-enforced marine reserves can dramatically increase fish biomass and biodiversity within their boundaries, with benefits spilling over into adjacent areas. International agreements to reduce plastic pollution and regulate fishing are being negotiated, though progress has been slow.\n\nThe deep ocean remains one of the least explored environments on Earth. More than eighty percent of the ocean floor has never been mapped in detail, and new species are regularly discovered in the abyssal depths. This unexplored frontier may hold valuable resources, including mineral deposits and novel compounds with pharmaceutical applications, but it also faces growing threats from deep-sea mining and other industrial activities.",

                "Climate change represents one of the most complex and consequential challenges facing humanity in the twenty-first century. The scientific consensus, based on decades of research and thousands of studies, is unequivocal: the Earth's climate is warming, and human activities — primarily the burning of fossil fuels and deforestation — are the dominant cause. The concentration of carbon dioxide in the atmosphere has risen from approximately 280 parts per million before the Industrial Revolution to more than 420 parts per million today, a level not seen for at least three million years.\n\nThe consequences of this warming are already being felt around the world. Global average temperatures have risen by approximately 1.1 degrees Celsius above pre-industrial levels, and the effects are not evenly distributed. The Arctic is warming at more than twice the global average rate, causing dramatic reductions in sea ice extent and threatening the survival of species such as polar bears and walruses. Glaciers on every continent are retreating, reducing freshwater supplies for millions of people who depend on glacial meltwater for drinking water and irrigation.\n\nExtreme weather events are becoming more frequent and intense. Heatwaves that would once have occurred once in fifty years are now happening every decade in many regions. Intense rainfall events are becoming more common as a warmer atmosphere holds more moisture. Tropical cyclones are intensifying more rapidly and producing more rainfall. Droughts are becoming more severe in already arid regions, threatening food security and driving migration.\n\nSea level rise poses an existential threat to low-lying coastal areas and small island nations. Global average sea levels have risen by approximately twenty centimetres since 1900, and the rate of rise is accelerating as ice sheets in Greenland and Antarctica melt. By the end of this century, sea levels could rise by a metre or more under high-emissions scenarios, inundating coastal cities and displacing hundreds of millions of people.\n\nThe Paris Agreement, adopted in 2015, committed countries to limiting global warming to well below two degrees Celsius above pre-industrial levels, with efforts to limit warming to 1.5 degrees. Achieving these targets requires rapid and deep reductions in greenhouse gas emissions across all sectors of the economy. The transition to renewable energy is accelerating, with solar and wind power now the cheapest sources of new electricity generation in most of the world. However, the pace of transition remains insufficient to meet the Paris targets, and current national commitments would lead to warming of approximately 2.7 degrees by the end of the century.",
            ],
            3 => [ // Passage 3 topics - Society / Psychology
                "Social media platforms have fundamentally altered the way people communicate, consume information, and present themselves to the world. Since the launch of Facebook in 2004 and the subsequent emergence of Twitter, Instagram, YouTube, and TikTok, billions of people have adopted these platforms as primary channels for social interaction, news consumption, and self-expression. The average person now spends more than two hours per day on social media, a figure that rises significantly among teenagers and young adults.\n\nThe psychological effects of social media use have been the subject of intense research and debate. Studies have found associations between heavy social media use and increased rates of anxiety, depression, and loneliness, particularly among adolescents. Researchers have proposed several mechanisms to explain these associations. Social comparison — measuring oneself against the carefully curated highlight reels that others post — can generate feelings of inadequacy and envy. The intermittent reinforcement provided by likes, comments, and shares activates the brain's reward system in ways that can lead to compulsive checking behaviour.\n\nHowever, the relationship between social media and mental health is complex and not uniformly negative. For many people, particularly those who are geographically isolated or belong to marginalised groups, social media provides a vital source of community and support. LGBTQ+ youth in conservative communities, people with rare medical conditions, and individuals with niche interests have all found online communities that provide connection and validation unavailable in their immediate physical environment.\n\nThe spread of misinformation through social media has emerged as a major societal concern. False and misleading content spreads faster and further than accurate information on social platforms, partly because it tends to be more emotionally engaging. During the COVID-19 pandemic, health misinformation spread rapidly on social media, undermining public health efforts and contributing to vaccine hesitancy. Political misinformation has been implicated in the erosion of trust in democratic institutions and the polarisation of public opinion.\n\nSocial media companies have faced growing pressure from governments, researchers, and civil society to address these harms. Platforms have implemented measures to label false content, reduce the amplification of misinformation, and provide users with tools to manage their usage. However, critics argue that these measures are insufficient and that the fundamental business model of social media — which depends on maximising engagement and therefore favours emotionally provocative content — is incompatible with the goal of promoting healthy online environments.\n\nRegulatory responses have varied across jurisdictions. The European Union's Digital Services Act, which came into force in 2023, imposes significant obligations on large platforms, including requirements to assess and mitigate systemic risks, provide transparency about algorithmic recommendation systems, and give users more control over their online experience. In the United States, legislative efforts to reform social media have been hampered by political disagreements and concerns about free speech.",

                "The psychology of decision-making has been transformed by decades of research revealing the systematic ways in which human judgment deviates from the rational model assumed by classical economics. The work of psychologists Daniel Kahneman and Amos Tversky, which earned Kahneman the Nobel Prize in Economics in 2002, demonstrated that people rely on mental shortcuts, or heuristics, that often lead to predictable errors.\n\nOne of the most influential concepts in behavioural economics is the distinction between two modes of thinking. System 1 thinking is fast, automatic, and intuitive — it operates below the level of conscious awareness and draws on pattern recognition and emotional responses. System 2 thinking is slow, deliberate, and analytical — it requires conscious effort and is used for complex reasoning and careful evaluation. Most everyday decisions are made by System 1, which is efficient but prone to biases.\n\nAnchoring is one of the most robust cognitive biases. When people are asked to estimate an unknown quantity, their judgments are heavily influenced by any number they have recently encountered, even if that number is clearly irrelevant. In one classic experiment, participants who were asked to spin a wheel of fortune before estimating the percentage of African countries in the United Nations gave significantly higher estimates if the wheel had stopped at a high number. The anchor had unconsciously influenced their judgment.\n\nLoss aversion — the tendency to feel losses more acutely than equivalent gains — has profound implications for economic behaviour. Research suggests that the pain of losing a sum of money is approximately twice as intense as the pleasure of gaining the same amount. This asymmetry leads people to make suboptimal decisions, such as holding onto losing investments in the hope of breaking even, or refusing to accept a fair gamble because the potential loss looms larger than the potential gain.\n\nThe concept of choice architecture — the way in which options are presented — has given rise to the field of nudge theory. By changing the default option, the order in which choices are presented, or the framing of information, policymakers can significantly influence behaviour without restricting freedom of choice. Opt-out organ donation systems, which make donation the default unless individuals actively choose otherwise, have dramatically increased donation rates in countries that have adopted them. Automatic enrolment in pension schemes has similarly increased retirement savings rates.\n\nBehavioural insights are increasingly being applied in public policy, healthcare, and business. Governments around the world have established behavioural insights teams to apply these principles to challenges ranging from tax compliance to energy conservation. However, critics have raised concerns about the paternalistic implications of nudging and the potential for these techniques to be used manipulatively by corporations and governments.",

                "Urban planning has evolved dramatically over the past century, shifting from a focus on functional efficiency to a more holistic concern with livability, sustainability, and social equity. The modernist planning movement of the mid-twentieth century, influenced by architects such as Le Corbusier, favoured the separation of land uses, the demolition of dense urban neighbourhoods, and the construction of high-rise housing towers surrounded by open space. These ideas, implemented in cities around the world, often produced environments that were alienating and socially dysfunctional.\n\nThe reaction against modernist planning gave rise to the New Urbanism movement, which advocates for compact, walkable, mixed-use neighbourhoods modelled on traditional town planning principles. New Urbanist developments prioritise pedestrian-friendly streets, a mix of housing types and price points, and the integration of shops, workplaces, and public spaces within walking distance of homes. Proponents argue that such environments foster social interaction, reduce car dependence, and support more sustainable lifestyles.\n\nThe concept of the fifteen-minute city, popularised by the French-Colombian urbanist Carlos Moreno, has gained significant traction in recent years. The idea is that all essential services — work, shopping, healthcare, education, and recreation — should be accessible within a fifteen-minute walk or cycle ride from home. Paris has adopted this concept as a guiding principle for urban development, investing in cycling infrastructure, converting car lanes to pedestrian and cycling paths, and encouraging the diversification of neighbourhood functions.\n\nGreen infrastructure has become an increasingly important element of urban planning. Trees, parks, green roofs, and urban wetlands provide a range of benefits, including cooling cities during heatwaves, managing stormwater, improving air quality, and supporting biodiversity. Research has shown that access to green space is associated with improved mental and physical health outcomes. Cities such as Singapore and Medellín have become internationally recognised for their innovative approaches to integrating nature into the urban fabric.\n\nHousing affordability has emerged as one of the most pressing challenges facing cities in many parts of the world. Rapid urbanisation, combined with restrictive zoning regulations that limit housing supply, has driven up property prices in many major cities to levels that are unaffordable for large segments of the population. The resulting displacement of lower-income residents from central urban areas has exacerbated social segregation and increased commuting distances.\n\nSmart city technologies — sensors, data analytics, and digital platforms — are being deployed in cities around the world to improve the efficiency of urban services and enhance the quality of life for residents. Traffic management systems that respond in real time to congestion, smart energy grids that balance supply and demand, and digital platforms that enable citizens to report problems and engage with local government are among the applications being piloted. However, concerns about data privacy, surveillance, and the digital divide have tempered enthusiasm for some of these technologies.",
            ],
        ];

        $passageIndex = ($testNumber - 1) % 3; // Cycle through 3 unique texts per slot
        return $passages[$passageNumber][$passageIndex] ?? $passages[1][0];
    }

    /**
     * Get a meaningful title for a listening exercise
     */
    private function getListeningTitle($bandLevel, $testNumber)
    {
        $titles = [
            'A Conversation About Course Registration',
            'A Lecture on Climate Change',
            'A Discussion on Project Planning',
            'An Interview on Renewable Energy',
            'A Talk on Urban Transport',
            'A Seminar on Digital Marketing',
            'A Conversation About Travel Plans',
            'A Lecture on Human Psychology',
            'A Discussion on Environmental Policy',
            'An Interview on Career Development',
            'A Talk on Healthy Lifestyles',
            'A Seminar on Global Economics',
            'A Conversation About Housing',
            'A Lecture on Space Exploration',
            'A Discussion on Education Reform',
            'An Interview on Technology Trends',
            'A Talk on Cultural Heritage',
            'A Seminar on Medical Research',
            'A Conversation About Community Projects',
            'A Lecture on Biodiversity',
        ];
        return $titles[($testNumber - 1) % count($titles)] . " ({$bandLevel})";
    }

    /**
     * Generate listening transcript for mock tests
     */
    private function getListeningTranscript($bandLevel, $testNumber, $sectionNumber)
    {
        $transcripts = [
            "Narrator: You will hear a conversation between a student and a university administrator about course registration. Student: Hello, I need help registering for next semester's courses. Administrator: Of course, let me pull up your file. What program are you in?",
            "Narrator: You will hear a lecture about climate change and its effects on global weather patterns. Professor: Today we'll examine how rising temperatures are affecting precipitation patterns worldwide.",
            "Narrator: You will hear a discussion between two colleagues about a new project proposal. Person A: Have you reviewed the budget estimates for the marketing campaign? Person B: Yes, I think we need to allocate more resources to digital advertising.",
            "Narrator: You will hear a radio interview with an environmental scientist discussing renewable energy. Interviewer: What are the most promising developments in solar technology? Scientist: Recent advances in photovoltaic efficiency have been remarkable.",
        ];
        
        return $transcripts[($sectionNumber - 1) % count($transcripts)] . " [Test {$testNumber}, Section {$sectionNumber} for {$bandLevel}]";
    }

    /**
     * Generate Writing Task 1 prompts
     */
    private function getWritingTask1Prompt($testNumber)
    {
        $prompts = [
            "The bar chart shows the percentage of households with internet access in five countries between 2010 and 2020.",
            "The line graph illustrates changes in average monthly rainfall in three cities over a 12-month period.",
            "The pie charts compare the sources of energy production in a country in 2000 and 2020.",
            "The table shows the number of international students enrolled in universities across four countries from 2015 to 2020.",
            "The process diagram illustrates how coffee is produced from bean to cup.",
            "The bar chart compares the amount of time people spend on different leisure activities in two age groups.",
            "The line graph shows trends in unemployment rates in three regions over a decade.",
            "The pie charts display the distribution of household expenses in 2010 and 2020.",
            "The table presents data on tourism revenue in five countries over five years.",
            "The diagram shows the stages involved in recycling plastic bottles.",
            "The bar chart illustrates the number of books read per year by different age groups.",
            "The line graph depicts changes in life expectancy in four countries from 1990 to 2020.",
            "The pie charts show the proportion of different transportation methods used by commuters in two years.",
            "The table displays statistics on smartphone ownership across various demographics.",
            "The process diagram explains how solar panels convert sunlight into electricity.",
            "The bar chart compares weekly working hours in different professions.",
            "The line graph shows fluctuations in stock market indices over a year.",
            "The pie charts illustrate changes in diet composition between two generations.",
            "The table presents data on air quality measurements in major cities.",
            "The diagram demonstrates the water treatment process in urban areas.",
        ];
        
        return $prompts[($testNumber - 1) % count($prompts)];
    }

    /**
     * Generate Writing Task 2 prompts
     */
    private function getWritingTask2Prompt($testNumber)
    {
        $prompts = [
            "Some people think that universities should provide graduates with the knowledge and skills needed in the workplace. Others think that the true function of a university should be to give access to knowledge for its own sake. Discuss both views and give your own opinion.",
            "In many countries, the proportion of older people is steadily increasing. Does this trend have more positive or negative effects on society?",
            "Some people believe that unpaid community service should be a compulsory part of high school programs. To what extent do you agree or disagree?",
            "Many people prefer to watch foreign films rather than locally produced films. Why could this be? Should governments give more financial support to local film industries?",
            "Some experts believe that it is better for children to begin learning a foreign language at primary school rather than secondary school. Do the advantages of this outweigh the disadvantages?",
            "In some countries, young people are encouraged to work or travel for a year between finishing high school and starting university studies. Discuss the advantages and disadvantages for young people who decide to do this.",
            "Successful sports professionals can earn a great deal more money than people in other important professions. Some people think this is fully justified while others think it is unfair. Discuss both views and give your own opinion.",
            "Some people think that parents should teach children how to be good members of society. Others believe that school is the place to learn this. Discuss both views and give your own opinion.",
            "Many museums charge for admission while others are free. Do you think the advantages of charging people for admission to museums outweigh the disadvantages?",
            "Some people think that strict punishments for driving offences are the key to reducing traffic accidents. Others believe that other measures would be more effective. Discuss both views and give your own opinion.",
            "In many parts of the world, children and teenagers are committing more crimes. Why is this happening? How should they be punished?",
            "Some people think that instead of preventing climate change, we need to find a way to live with it. To what extent do you agree or disagree?",
            "Many people believe that social networking sites have had a huge negative impact on both individuals and society. To what extent do you agree or disagree?",
            "In some countries, owning a home rather than renting one is very important for people. Why might this be the case? Do you think this is a positive or negative situation?",
            "Some people say that advertising encourages us to buy things we really do not need. Others say that advertisements tell us about new products that may improve our lives. Which viewpoint do you agree with?",
            "In many countries, people are now living longer than ever before. Some people say an ageing population creates problems for governments. Other people think there are benefits if society has more elderly people. To what extent do you agree or disagree?",
            "Some people think that all university students should study whatever they like. Others believe that they should only be allowed to study subjects that will be useful in the future, such as those related to science and technology. Discuss both views and give your own opinion.",
            "In many countries, traditional foods are being replaced by international fast foods. This is having a negative effect on both families and societies. To what extent do you agree or disagree?",
            "Some people believe that it is best to accept a bad situation, such as an unsatisfactory job or shortage of money. Others argue that it is better to try and improve such situations. Discuss both views and give your own opinion.",
            "Some people think that the government is wasting money on the arts and that this money could be better spent elsewhere. To what extent do you agree with this view?",
        ];
        
        return $prompts[($testNumber - 1) % count($prompts)];
    }

    /**
     * Generate Speaking Part 1 prompts
     */
    private function getSpeakingPart1Prompt($testNumber)
    {
        $prompts = [
            "Let's talk about your hometown. Where are you from? What do you like about your hometown? Has your hometown changed much since you were a child?",
            "Let's talk about your work or studies. What do you do? Why did you choose this field? What do you find most interesting about your work/studies?",
            "Let's talk about your hobbies. What do you like to do in your free time? How long have you been interested in this hobby? Do you think hobbies are important?",
            "Let's talk about food. What is your favorite type of food? Do you enjoy cooking? Have your food preferences changed over time?",
            "Let's talk about technology. How often do you use technology? What technological device is most important to you? How has technology changed your life?",
            "Let's talk about travel. Do you enjoy traveling? Where have you traveled recently? What type of places do you prefer to visit?",
            "Let's talk about music. What kind of music do you like? Do you play any musical instruments? How does music make you feel?",
            "Let's talk about sports. Do you play any sports? What sports are popular in your country? Do you prefer watching or playing sports?",
            "Let's talk about reading. Do you enjoy reading? What types of books do you prefer? How often do you read?",
            "Let's talk about your daily routine. What does a typical day look like for you? What part of your day do you enjoy most? Would you like to change anything about your routine?",
            "Let's talk about friends. How do you usually spend time with friends? What qualities do you value in a friend? Have your friendships changed over the years?",
            "Let's talk about weather. What's the weather like in your country? What's your favorite season? How does weather affect your mood?",
            "Let's talk about shopping. Do you enjoy shopping? What do you usually shop for? Do you prefer shopping online or in stores?",
            "Let's talk about transportation. How do you usually get around? What's the transportation system like in your city? Have you ever had any interesting experiences while traveling?",
            "Let's talk about learning. What's the best way for you to learn new things? What have you learned recently? Do you enjoy learning new skills?",
            "Let's talk about your home. Can you describe where you live? What do you like about your home? Would you like to move somewhere else?",
            "Let's talk about celebrations. What celebrations are important in your culture? How do you usually celebrate special occasions? What was your most memorable celebration?",
            "Let's talk about nature. Do you spend much time in nature? What natural places do you enjoy visiting? Why is nature important to you?",
            "Let's talk about communication. How do you prefer to communicate with others? Has the way you communicate changed over time? What communication skills are most important?",
            "Let's talk about goals. What are your current goals? How do you work towards achieving your goals? What motivates you to pursue your goals?",
        ];
        
        return $prompts[($testNumber - 1) % count($prompts)];
    }

    /**
     * Generate Speaking Part 2 prompts
     */
    private function getSpeakingPart2Prompt($testNumber)
    {
        $prompts = [
            "Describe a person who has influenced you. You should say: who this person is, how you know them, what influence they have had on you, and explain why this person is important to you.",
            "Describe a place you would like to visit. You should say: where it is, what you know about it, why you want to visit it, and explain what you would do there.",
            "Describe an important event in your life. You should say: what the event was, when it happened, who was involved, and explain why it was important to you.",
            "Describe a skill you would like to learn. You should say: what the skill is, why you want to learn it, how you would learn it, and explain how it would benefit you.",
            "Describe a book or film that had a strong impact on you. You should say: what it was about, when you read/watched it, why it impacted you, and explain what you learned from it.",
            "Describe a challenge you have overcome. You should say: what the challenge was, when you faced it, how you overcame it, and explain what you learned from the experience.",
            "Describe your ideal job. You should say: what the job is, what it involves, why it appeals to you, and explain what you would need to do to get this job.",
            "Describe a tradition in your family or culture. You should say: what the tradition is, how long it has existed, how it is practiced, and explain why it is important.",
            "Describe a time when you helped someone. You should say: who you helped, what you did, why you helped them, and explain how you felt about helping them.",
            "Describe an object that is special to you. You should say: what it is, how you got it, what you use it for, and explain why it is special to you.",
            "Describe a memorable journey you have taken. You should say: where you went, who you went with, what you did, and explain why it was memorable.",
            "Describe a teacher who influenced you. You should say: who the teacher was, what subject they taught, how they influenced you, and explain why they were important to you.",
            "Describe a time when you tried something new. You should say: what you tried, when you tried it, how it went, and explain how you felt about the experience.",
            "Describe a goal you have achieved. You should say: what the goal was, how you achieved it, what challenges you faced, and explain how you felt when you achieved it.",
            "Describe a piece of advice you received. You should say: what the advice was, who gave it to you, when you received it, and explain how it helped you.",
            "Describe a festival or celebration you enjoy. You should say: what it is, when it takes place, how you celebrate it, and explain why you enjoy it.",
            "Describe a decision you made that changed your life. You should say: what the decision was, when you made it, what influenced your decision, and explain how it changed your life.",
            "Describe a hobby you enjoy. You should say: what the hobby is, how you started it, how often you do it, and explain why you enjoy it.",
            "Describe a time when you felt proud. You should say: what happened, when it happened, why you felt proud, and explain what this experience meant to you.",
            "Describe a change you would like to see in your community. You should say: what the change is, why it is needed, how it could be implemented, and explain how it would benefit the community.",
        ];
        
        return $prompts[($testNumber - 1) % count($prompts)];
    }

    /**
     * Generate Speaking Part 3 prompts
     */
    private function getSpeakingPart3Prompt($testNumber)
    {
        $prompts = [
            "Let's discuss education. How has education changed in recent years? What role should technology play in education? Do you think everyone should have access to higher education?",
            "Let's discuss work and careers. How has the nature of work changed? What skills will be most important in the future? Should people prioritize job satisfaction or salary?",
            "Let's discuss environmental issues. What are the biggest environmental challenges we face? Who is responsible for protecting the environment? How can individuals contribute to environmental protection?",
            "Let's discuss technology and society. How has technology changed the way we communicate? What are the benefits and drawbacks of social media? Will technology make life better or worse in the future?",
            "Let's discuss family and relationships. How have family structures changed? What makes a relationship successful? How important is family in modern society?",
            "Let's discuss health and lifestyle. What factors contribute to a healthy lifestyle? Should governments regulate unhealthy foods? How can people be encouraged to exercise more?",
            "Let's discuss culture and tradition. Why is it important to preserve cultural traditions? How does globalization affect local cultures? Can traditional and modern values coexist?",
            "Let's discuss cities and urban planning. What makes a city livable? Should cities prioritize cars or public transportation? How can cities become more sustainable?",
            "Let's discuss media and information. How has the internet changed access to information? What is the role of traditional media today? How can people identify reliable information?",
            "Let's discuss travel and tourism. How has tourism changed in recent years? What are the positive and negative effects of tourism? Should there be limits on tourism in certain places?",
            "Let's discuss crime and punishment. What are the main causes of crime? Is prison an effective punishment? How can society reduce crime rates?",
            "Let's discuss art and creativity. Why is art important in society? Should art be funded by governments? How does art reflect cultural values?",
            "Let's discuss sports and competition. What role does sport play in society? Should professional athletes be role models? Is competition always beneficial?",
            "Let's discuss money and success. How do people define success? Is money the most important factor in success? Has the definition of success changed over time?",
            "Let's discuss language and communication. Why is it important to learn foreign languages? Will English remain the global language? How has technology affected language?",
            "Let's discuss aging and generations. How should society support elderly people? What can younger generations learn from older ones? Are generational differences increasing?",
            "Let's discuss food and agriculture. How has food production changed? Should people eat less meat? What is the future of farming?",
            "Let's discuss privacy and security. How important is privacy in modern society? Should governments have access to personal data? How can people protect their privacy online?",
            "Let's discuss leadership and management. What makes a good leader? Are leaders born or made? How has leadership changed in modern organizations?",
            "Let's discuss innovation and change. Why is innovation important? How can societies encourage innovation? What are the risks of rapid change?",
        ];
        
        return $prompts[($testNumber - 1) % count($prompts)];
    }
    
    /**
     * Create questions for a reading passage
     * Questions are matched to the actual passage content
     */
    private function createQuestionsForPassage($passage, $bandLevel, $passageNumber)
    {
        // Question sets matched to each passage slot
        $questionSets = [
            1 => [ // Technology / Science passages
                [
                    ['text' => 'What is the main subject of this passage?', 'type' => 'fill_blank', 'answer' => 'artificial intelligence'],
                    ['text' => 'According to the passage, which industries have been transformed by AI?', 'type' => 'multiple_choice', 'answer' => 'healthcare and finance', 'options' => ['education and retail', 'healthcare and finance', 'agriculture and mining', 'tourism and hospitality']],
                    ['text' => 'Machine learning algorithms enable more accurate what?', 'type' => 'fill_blank', 'answer' => 'predictions'],
                    ['text' => 'AI systems learn from data rather than following pre-programmed rules.', 'type' => 'true_false', 'answer' => 'TRUE'],
                    ['text' => 'What term describes systems capable of performing any intellectual task a human can?', 'type' => 'fill_blank', 'answer' => 'artificial general intelligence'],
                ],
                [
                    ['text' => 'Who is credited with designing the Analytical Engine?', 'type' => 'fill_blank', 'answer' => 'Charles Babbage'],
                    ['text' => 'What replaced vacuum tubes in computers?', 'type' => 'multiple_choice', 'answer' => 'transistors', 'options' => ['transistors', 'microchips', 'capacitors', 'resistors']],
                    ['text' => 'The World Wide Web was invented by Tim Berners-Lee.', 'type' => 'true_false', 'answer' => 'TRUE'],
                    ['text' => 'What observation about transistor density became known as Moore\'s Law?', 'type' => 'fill_blank', 'answer' => 'doubling every two years'],
                    ['text' => 'Which technology promises to solve problems intractable for classical computers?', 'type' => 'fill_blank', 'answer' => 'quantum computing'],
                ],
                [
                    ['text' => 'What was the name of the first artificial satellite?', 'type' => 'fill_blank', 'answer' => 'Sputnik'],
                    ['text' => 'Which telescope has provided images of distant galaxies?', 'type' => 'multiple_choice', 'answer' => 'Hubble Space Telescope', 'options' => ['James Webb Telescope', 'Hubble Space Telescope', 'Chandra Observatory', 'Spitzer Telescope']],
                    ['text' => 'Private companies have reduced the cost of reaching orbit.', 'type' => 'true_false', 'answer' => 'TRUE'],
                    ['text' => 'What is the name of SpaceX\'s satellite internet constellation?', 'type' => 'fill_blank', 'answer' => 'Starlink'],
                    ['text' => 'NASA\'s programme to return astronauts to the Moon is called what?', 'type' => 'fill_blank', 'answer' => 'Artemis'],
                ],
            ],
            2 => [ // Environment / Nature passages
                [
                    ['text' => 'What term describes the variety of life on Earth?', 'type' => 'fill_blank', 'answer' => 'biodiversity'],
                    ['text' => 'Approximately what fraction of the world\'s flowering plants depend on pollinators?', 'type' => 'multiple_choice', 'answer' => 'three-quarters', 'options' => ['one-quarter', 'one-half', 'three-quarters', 'all']],
                    ['text' => 'The current rate of species extinction is higher than the natural background rate.', 'type' => 'true_false', 'answer' => 'TRUE'],
                    ['text' => 'What percentage of Earth\'s land surface is covered by protected areas?', 'type' => 'fill_blank', 'answer' => 'fifteen percent'],
                    ['text' => 'Which primate has seen its population grow from fewer than 300 to over 1,000?', 'type' => 'fill_blank', 'answer' => 'mountain gorilla'],
                ],
                [
                    ['text' => 'What percentage of the Earth\'s surface do oceans cover?', 'type' => 'fill_blank', 'answer' => 'more than seventy percent'],
                    ['text' => 'What are the microscopic marine plants that produce oxygen called?', 'type' => 'multiple_choice', 'answer' => 'phytoplankton', 'options' => ['zooplankton', 'phytoplankton', 'algae', 'diatoms']],
                    ['text' => 'Plastic pollution has no effect on marine mammals.', 'type' => 'true_false', 'answer' => 'FALSE'],
                    ['text' => 'How many tonnes of plastic enter the oceans each year?', 'type' => 'fill_blank', 'answer' => 'eight million tonnes'],
                    ['text' => 'What percentage of the ocean floor has never been mapped in detail?', 'type' => 'fill_blank', 'answer' => 'more than eighty percent'],
                ],
                [
                    ['text' => 'By how much have global average temperatures risen above pre-industrial levels?', 'type' => 'fill_blank', 'answer' => '1.1 degrees Celsius'],
                    ['text' => 'Which agreement committed countries to limiting warming to well below two degrees?', 'type' => 'multiple_choice', 'answer' => 'Paris Agreement', 'options' => ['Kyoto Protocol', 'Paris Agreement', 'Copenhagen Accord', 'Montreal Protocol']],
                    ['text' => 'The Arctic is warming faster than the global average.', 'type' => 'true_false', 'answer' => 'TRUE'],
                    ['text' => 'By how much have global sea levels risen since 1900?', 'type' => 'fill_blank', 'answer' => 'approximately twenty centimetres'],
                    ['text' => 'What is the current atmospheric concentration of carbon dioxide?', 'type' => 'fill_blank', 'answer' => 'more than 420 parts per million'],
                ],
            ],
            3 => [ // Society / Psychology passages
                [
                    ['text' => 'How many hours per day does the average person spend on social media?', 'type' => 'fill_blank', 'answer' => 'more than two hours'],
                    ['text' => 'What EU regulation imposes obligations on large social media platforms?', 'type' => 'multiple_choice', 'answer' => 'Digital Services Act', 'options' => ['GDPR', 'Digital Services Act', 'Digital Markets Act', 'AI Act']],
                    ['text' => 'Social media has only negative effects on mental health.', 'type' => 'true_false', 'answer' => 'FALSE'],
                    ['text' => 'What term describes measuring oneself against others\' online posts?', 'type' => 'fill_blank', 'answer' => 'social comparison'],
                    ['text' => 'False content spreads faster than accurate information on social platforms.', 'type' => 'true_false', 'answer' => 'TRUE'],
                ],
                [
                    ['text' => 'Who won the Nobel Prize in Economics in 2002 for work on decision-making?', 'type' => 'fill_blank', 'answer' => 'Daniel Kahneman'],
                    ['text' => 'What is the term for fast, automatic, intuitive thinking?', 'type' => 'multiple_choice', 'answer' => 'System 1', 'options' => ['System 1', 'System 2', 'Heuristic thinking', 'Analytical thinking']],
                    ['text' => 'People feel losses more acutely than equivalent gains.', 'type' => 'true_false', 'answer' => 'TRUE'],
                    ['text' => 'What is the field that applies behavioural insights to policy called?', 'type' => 'fill_blank', 'answer' => 'nudge theory'],
                    ['text' => 'Anchoring refers to the influence of a recently encountered number on estimates.', 'type' => 'true_false', 'answer' => 'TRUE'],
                ],
                [
                    ['text' => 'What concept advocates for all services within a fifteen-minute walk or cycle?', 'type' => 'fill_blank', 'answer' => 'fifteen-minute city'],
                    ['text' => 'Which architect influenced the modernist planning movement?', 'type' => 'multiple_choice', 'answer' => 'Le Corbusier', 'options' => ['Frank Lloyd Wright', 'Le Corbusier', 'Zaha Hadid', 'Norman Foster']],
                    ['text' => 'New Urbanism favours the separation of land uses.', 'type' => 'true_false', 'answer' => 'FALSE'],
                    ['text' => 'Which city adopted the fifteen-minute city as a guiding planning principle?', 'type' => 'fill_blank', 'answer' => 'Paris'],
                    ['text' => 'Green infrastructure helps cool cities during heatwaves.', 'type' => 'true_false', 'answer' => 'TRUE'],
                ],
            ],
        ];

        $passageIndex = ($passage->id ?? 0) % 3; // Vary questions based on passage
        $questionSet = $questionSets[$passageNumber][$passageIndex] ?? $questionSets[1][0];

        foreach ($questionSet as $questionData) {
            \App\Models\Question::create([
                'passage_id' => $passage->id,
                'question_text' => $questionData['text'],
                'question_type' => $questionData['type'],
                'correct_answer' => $questionData['answer'],
                'options' => $questionData['options'] ?? null,
                'points' => 1,
                'ielts_band_level' => str_replace('band', '', $bandLevel),
                'is_ai_generated' => false,
            ]);
        }
    }
    
    /**
     * Create questions for a listening exercise
     */
    private function createQuestionsForListening($exercise, $testNumber)
    {
        $questionSets = [
            [
                ['text' => 'What is the main purpose of this conversation?', 'type' => 'fill_blank', 'answer' => 'course registration'],
                ['text' => 'Who is the student speaking with?', 'type' => 'multiple_choice', 'answer' => 'administrator', 'options' => ['professor', 'administrator', 'counselor', 'librarian']],
                ['text' => 'What does the student need help with?', 'type' => 'fill_blank', 'answer' => 'registering for courses'],
                ['text' => 'Is this conversation taking place at a university?', 'type' => 'true_false', 'answer' => 'TRUE'],
                ['text' => 'What information does the administrator request first?', 'type' => 'fill_blank', 'answer' => 'student file'],
            ],
            [
                ['text' => 'What is the main topic of this lecture?', 'type' => 'fill_blank', 'answer' => 'climate change'],
                ['text' => 'What is the professor examining in this lecture?', 'type' => 'multiple_choice', 'answer' => 'precipitation patterns', 'options' => ['ocean levels', 'precipitation patterns', 'wind speeds', 'temperature records']],
                ['text' => 'Rising temperatures are affecting weather patterns worldwide.', 'type' => 'true_false', 'answer' => 'TRUE'],
                ['text' => 'What subject does the professor teach?', 'type' => 'fill_blank', 'answer' => 'environmental science'],
                ['text' => 'The lecture focuses on global or local weather patterns?', 'type' => 'fill_blank', 'answer' => 'global'],
            ],
            [
                ['text' => 'What are the two colleagues discussing?', 'type' => 'fill_blank', 'answer' => 'project proposal'],
                ['text' => 'What aspect of the campaign needs more resources?', 'type' => 'multiple_choice', 'answer' => 'digital advertising', 'options' => ['print media', 'digital advertising', 'television', 'radio']],
                ['text' => 'Person B has already reviewed the budget estimates.', 'type' => 'true_false', 'answer' => 'TRUE'],
                ['text' => 'What type of campaign are they discussing?', 'type' => 'fill_blank', 'answer' => 'marketing campaign'],
                ['text' => 'Do both colleagues agree on the budget allocation?', 'type' => 'fill_blank', 'answer' => 'no'],
            ],
            [
                ['text' => 'What field does the interviewee work in?', 'type' => 'fill_blank', 'answer' => 'environmental science'],
                ['text' => 'What technology is discussed in the interview?', 'type' => 'multiple_choice', 'answer' => 'solar technology', 'options' => ['wind power', 'solar technology', 'hydroelectric', 'nuclear energy']],
                ['text' => 'Recent advances in photovoltaic efficiency have been remarkable.', 'type' => 'true_false', 'answer' => 'TRUE'],
                ['text' => 'What format is this audio recording?', 'type' => 'fill_blank', 'answer' => 'radio interview'],
                ['text' => 'What is the scientist\'s area of expertise?', 'type' => 'fill_blank', 'answer' => 'renewable energy'],
            ],
            [
                ['text' => 'What is the main subject of this talk?', 'type' => 'fill_blank', 'answer' => 'urban transport'],
                ['text' => 'What type of transport is being promoted?', 'type' => 'multiple_choice', 'answer' => 'public transport', 'options' => ['private cars', 'public transport', 'cycling only', 'walking']],
                ['text' => 'Urban transport affects quality of life in cities.', 'type' => 'true_false', 'answer' => 'TRUE'],
                ['text' => 'What problem does the speaker identify?', 'type' => 'fill_blank', 'answer' => 'traffic congestion'],
                ['text' => 'Is the speaker in favour of reducing car use?', 'type' => 'fill_blank', 'answer' => 'yes'],
            ],
        ];

        $set = $questionSets[($testNumber - 1) % count($questionSets)];

        foreach ($set as $questionData) {
            \App\Models\ListeningQuestion::create([
                'listening_exercise_id' => $exercise->id,
                'question_text' => $questionData['text'],
                'question_type' => $questionData['type'],
                'correct_answer' => $questionData['answer'],
                'options' => $questionData['options'] ?? null,
                'points' => 1,
            ]);
        }
    }
}
