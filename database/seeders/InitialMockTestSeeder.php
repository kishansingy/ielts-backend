<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MockTest;
use App\Models\MockTestSection;
use App\Models\ReadingPassage;
use App\Models\Question;
use App\Models\ListeningExercise;
use App\Models\ListeningQuestion;
use App\Models\WritingTask;
use App\Models\SpeakingPrompt;
use Illuminate\Support\Facades\DB;

/**
 * Seeds 1 complete mock test per band level (4 total) with static content.
 * No AI / no API calls — works offline, safe for fresh deployments.
 *
 * Run: php artisan db:seed --class=InitialMockTestSeeder
 *
 * After this, the daily scheduler (mocktests:generate-daily) adds 1 AI test
 * per day automatically, growing the library over time.
 */
class InitialMockTestSeeder extends Seeder
{
    private int $adminId = 1;

    public function run(): void
    {
        $admin = \App\Models\User::where('role', 'admin')->first()
            ?? \App\Models\User::first();

        if (!$admin) {
            $this->command->error('No user found. Run migrations and UserSeeder first.');
            return;
        }

        $this->adminId = $admin->id;

        $this->command->info('Seeding initial mock tests (1 per band, no AI required)...');

        DB::transaction(function () {
            $this->seedBand6();
            $this->seedBand7();
            $this->seedBand8();
            $this->seedBand9();
        });

        $total = MockTest::count();
        $this->command->info("Done. {$total} mock tests available.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BAND 6.5 — Lower intermediate: simple vocabulary, everyday topics
    // ─────────────────────────────────────────────────────────────────────────

    private function seedBand6(): void
    {
        $this->command->line('  Creating Band 6.5 test...');

        $test = MockTest::create([
            'title'            => 'IELTS Mock Test 1 - BAND6',
            'description'      => 'Complete IELTS practice test 1 for band 6.5 level. Includes Reading, Listening, Writing, and Speaking sections.',
            'band_level'       => 'band6',
            'duration_minutes' => 180,
            'is_active'        => true,
            'available_from'   => now(),
        ]);

        $order = 1;
        $order = $this->createReading($test, 'band6', 'beginner', $order, [
            'title'   => 'The Benefits of Urban Green Spaces',
            'content' => 'Urban green spaces, such as parks, gardens, and tree-lined streets, play an important role in city life. These areas provide places for people to relax, exercise, and spend time with family and friends. Research shows that spending time in green spaces can reduce stress and improve mental health. People who live near parks tend to feel happier and healthier than those who do not.

Green spaces also help the environment in cities. Trees and plants absorb carbon dioxide and produce oxygen, which improves air quality. They also provide shade, which helps to reduce the temperature in cities during hot weather. This effect is known as the urban heat island effect, and green spaces help to reduce it.

In addition, green spaces support wildlife. Birds, insects, and small animals can find food and shelter in parks and gardens. This is important because many wild animals have lost their natural habitats due to urban development. By creating and maintaining green spaces, cities can help to protect local wildlife.

However, maintaining green spaces costs money. City governments must pay for gardeners, equipment, and water. Some people argue that this money could be better spent on other services, such as schools or hospitals. Despite this, many experts believe that the benefits of green spaces are worth the cost. A healthy, green city is a better place to live for everyone.',
            'questions' => [
                ['text' => 'What is one benefit of green spaces mentioned in the first paragraph?', 'type' => 'multiple_choice', 'answer' => 'They reduce stress and improve mental health', 'options' => ['They increase property prices', 'They reduce stress and improve mental health', 'They provide jobs for gardeners', 'They attract tourists to the city']],
                ['text' => 'Trees and plants absorb ___ and produce oxygen.', 'type' => 'fill_blank', 'answer' => 'carbon dioxide'],
                ['text' => 'What is the urban heat island effect?', 'type' => 'multiple_choice', 'answer' => 'Higher temperatures in cities compared to surrounding areas', 'options' => ['Higher temperatures in cities compared to surrounding areas', 'A type of garden found in cities', 'Pollution caused by cars', 'Flooding in urban areas']],
                ['text' => 'True or False: Green spaces help protect local wildlife.', 'type' => 'true_false', 'answer' => 'TRUE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'Why have many wild animals lost their natural habitats?', 'type' => 'fill_blank', 'answer' => 'urban development'],
                ['text' => 'True or False: Everyone agrees that money spent on green spaces is wasted.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'What must city governments pay for to maintain green spaces?', 'type' => 'multiple_choice', 'answer' => 'Gardeners, equipment, and water', 'options' => ['New buildings and roads', 'Gardeners, equipment, and water', 'Schools and hospitals', 'Public transport systems']],
                ['text' => 'People who live near parks tend to feel ___ and healthier.', 'type' => 'fill_blank', 'answer' => 'happier'],
                ['text' => 'True or False: Green spaces have no effect on air quality.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'What do birds and insects find in parks and gardens?', 'type' => 'fill_blank', 'answer' => 'food and shelter'],
            ],
        ]);

        $order = $this->createListening($test, 'band6', 'beginner', $order, [
            'title'    => 'Booking a Sports Centre Membership',
            'script'   => 'Receptionist: Good morning, Riverside Sports Centre. How can I help you?
Customer: Hello, I would like to find out about membership options please.
Receptionist: Of course. We have three types of membership. The basic membership costs twenty pounds per month and gives you access to the gym and swimming pool.
Customer: What about fitness classes?
Receptionist: Fitness classes are included in our standard membership, which is thirty-five pounds per month. Our premium membership is fifty pounds per month and includes everything plus personal training sessions.
Customer: How many personal training sessions are included?
Receptionist: You get two sessions per month with the premium membership. Additional sessions cost fifteen pounds each.
Customer: What are the opening hours?
Receptionist: We are open Monday to Friday from six in the morning until ten at night. On weekends we open at eight and close at eight.
Customer: Is there parking available?
Receptionist: Yes, we have free parking for members. Non-members pay two pounds per hour.
Customer: Great. I would like to sign up for the standard membership please.
Receptionist: Wonderful. I will need your name and contact details to get started.',
            'questions' => [
                ['text' => 'How much does the basic membership cost per month?', 'type' => 'fill_blank', 'answer' => 'twenty pounds'],
                ['text' => 'Which membership includes fitness classes?', 'type' => 'multiple_choice', 'answer' => 'Standard membership', 'options' => ['Basic membership', 'Standard membership', 'Premium membership', 'All memberships']],
                ['text' => 'How much is the premium membership per month?', 'type' => 'fill_blank', 'answer' => 'fifty pounds'],
                ['text' => 'How many personal training sessions are included in premium membership?', 'type' => 'multiple_choice', 'answer' => 'Two per month', 'options' => ['One per month', 'Two per month', 'Three per month', 'Four per month']],
                ['text' => 'True or False: The sports centre is open until 10pm on Saturdays.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'What time does the centre open on weekdays?', 'type' => 'fill_blank', 'answer' => 'six in the morning'],
                ['text' => 'How much do non-members pay for parking per hour?', 'type' => 'fill_blank', 'answer' => 'two pounds'],
                ['text' => 'True or False: Parking is free for members.', 'type' => 'true_false', 'answer' => 'TRUE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'Which membership did the customer choose?', 'type' => 'multiple_choice', 'answer' => 'Standard membership', 'options' => ['Basic membership', 'Standard membership', 'Premium membership', 'Trial membership']],
                ['text' => 'How much does an additional personal training session cost?', 'type' => 'fill_blank', 'answer' => 'fifteen pounds'],
            ],
        ]);

        $order = $this->createWriting($test, 'band6', $order, [
            ['type' => 'task1', 'prompt' => 'The bar chart below shows the number of people who visited a local library each month from January to June. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.', 'instructions' => 'Write at least 150 words.', 'time' => 20, 'words' => 150],
            ['type' => 'task2', 'prompt' => 'Some people think that children should spend more time doing outdoor activities rather than playing video games. To what extent do you agree or disagree?', 'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.', 'time' => 40, 'words' => 250],
        ]);

        $this->createSpeaking($test, 'band6', 'beginner', $order, [
            'part1' => "1. Can you tell me about where you live?\n2. Do you enjoy spending time outdoors?\n3. What do you usually do at weekends?\n4. Do you prefer living in a city or the countryside?",
            'part2' => "Describe a place in your town or city that you enjoy visiting.\nYou should say:\n- where it is\n- what you can do there\n- who you usually go with\nand explain why you enjoy going there.",
            'part3' => "1. How important are public spaces in a city?\n2. Do you think cities have enough green spaces?\n3. How has your local area changed in recent years?\n4. What can governments do to improve cities for residents?",
        ]);

        $this->command->line('  <fg=green>✓ Band 6.5 test created</>');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BAND 7 — Upper intermediate: academic vocabulary, complex sentences
    // ─────────────────────────────────────────────────────────────────────────

    private function seedBand7(): void
    {
        $this->command->line('  Creating Band 7 test...');

        $test = MockTest::create([
            'title'            => 'IELTS Mock Test 1 - BAND7',
            'description'      => 'Complete IELTS practice test 1 for band 7 level.',
            'band_level'       => 'band7',
            'duration_minutes' => 180,
            'is_active'        => true,
            'available_from'   => now(),
        ]);

        $order = 1;
        $order = $this->createReading($test, 'band7', 'intermediate', $order, [
            'title'   => 'The Psychology of Habit Formation',
            'content' => 'Habits are automatic behaviours triggered by specific cues in our environment. Neuroscientists have identified a three-part neurological loop that underlies all habitual behaviour: a cue, a routine, and a reward. This loop, sometimes called the habit loop, is encoded in the basal ganglia, a region of the brain associated with procedural learning and emotional processing. Understanding this mechanism has significant implications for both personal development and public health interventions.

Research by Ann Graybiel at the Massachusetts Institute of Technology demonstrated that as behaviours become habitual, brain activity in the prefrontal cortex — the region responsible for decision-making — decreases significantly. This neurological shift explains why habitual actions require minimal conscious effort and why breaking established habits is considerably more challenging than forming new ones. The brain, in effect, outsources routine decisions to more primitive neural structures.

The concept of keystone habits, introduced by journalist Charles Duhigg, suggests that certain habits have a disproportionate influence on other areas of behaviour. Regular exercise, for instance, has been shown to correlate with improved dietary choices, better sleep patterns, and increased productivity. This cascading effect occurs because keystone habits introduce structures that help other habits flourish.

Behavioural psychologists have identified several evidence-based strategies for habit modification. Implementation intentions — specific plans that link a desired behaviour to a particular time and location — have been shown to significantly increase the likelihood of behaviour change. Similarly, habit stacking, which involves attaching a new behaviour to an existing routine, leverages the brain\'s existing neural pathways to facilitate the adoption of new habits.

Environmental design represents another powerful tool for habit formation. Research consistently demonstrates that modifying the physical environment to make desired behaviours easier and undesired behaviours more difficult is more effective than relying on willpower alone. This insight has been applied in public health campaigns, workplace wellness programmes, and urban planning initiatives worldwide.',
            'questions' => [
                ['text' => 'What are the three components of the habit loop?', 'type' => 'fill_blank', 'answer' => 'a cue, a routine, and a reward'],
                ['text' => 'Which brain region is associated with habitual behaviour?', 'type' => 'multiple_choice', 'answer' => 'The basal ganglia', 'options' => ['The prefrontal cortex', 'The basal ganglia', 'The hippocampus', 'The cerebellum']],
                ['text' => 'True or False: Brain activity in the prefrontal cortex increases as behaviours become habitual.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'Who conducted research at MIT on habit formation?', 'type' => 'fill_blank', 'answer' => 'Ann Graybiel'],
                ['text' => 'What term describes habits that influence other areas of behaviour?', 'type' => 'multiple_choice', 'answer' => 'Keystone habits', 'options' => ['Core habits', 'Keystone habits', 'Foundation habits', 'Primary habits']],
                ['text' => 'True or False: Regular exercise has been shown to improve dietary choices.', 'type' => 'true_false', 'answer' => 'TRUE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'What are implementation intentions?', 'type' => 'fill_blank', 'answer' => 'specific plans that link a desired behaviour to a particular time and location'],
                ['text' => 'What does habit stacking involve?', 'type' => 'multiple_choice', 'answer' => 'Attaching a new behaviour to an existing routine', 'options' => ['Doing multiple habits simultaneously', 'Attaching a new behaviour to an existing routine', 'Replacing bad habits with good ones', 'Tracking habits in a journal']],
                ['text' => 'True or False: Willpower alone is the most effective tool for habit formation according to research.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'What does environmental design involve in the context of habit formation?', 'type' => 'fill_blank', 'answer' => 'modifying the physical environment to make desired behaviours easier'],
            ],
        ]);

        $order = $this->createListening($test, 'band7', 'intermediate', $order, [
            'title'    => 'University Seminar: Sustainable Urban Development',
            'script'   => 'Lecturer: Good afternoon everyone. Today we\'re going to examine the concept of sustainable urban development, focusing particularly on how cities can reduce their carbon footprint while maintaining economic growth. This is increasingly relevant given that urban areas currently account for approximately seventy percent of global carbon emissions.

Student A: Could you explain what distinguishes sustainable development from conventional urban planning?

Lecturer: Certainly. Conventional planning has historically prioritised economic growth and infrastructure expansion with limited consideration for environmental impact. Sustainable development, by contrast, integrates environmental, social, and economic objectives simultaneously. The concept was formally defined in the Brundtland Report of 1987 as development that meets the needs of the present without compromising the ability of future generations to meet their own needs.

Student B: What are some practical examples of sustainable urban initiatives?

Lecturer: There are several compelling examples. Copenhagen has committed to becoming the world\'s first carbon-neutral capital by 2025, primarily through investment in cycling infrastructure and district heating systems. Singapore has implemented extensive green building regulations requiring new developments to achieve minimum sustainability ratings. Curitiba in Brazil is frequently cited for its innovative bus rapid transit system, which has significantly reduced private vehicle use.

Student A: Are there economic arguments for sustainable development?

Lecturer: Absolutely. Research from the Global Commission on the Economy and Climate suggests that sustainable infrastructure investment generates returns of approximately four dollars for every dollar invested when accounting for health, productivity, and environmental benefits. The transition to sustainable cities is therefore not merely an environmental imperative but an economic opportunity.',
            'questions' => [
                ['text' => 'What percentage of global carbon emissions do urban areas account for?', 'type' => 'fill_blank', 'answer' => 'approximately seventy percent'],
                ['text' => 'In which year was the Brundtland Report published?', 'type' => 'multiple_choice', 'answer' => '1987', 'options' => ['1972', '1987', '1992', '2000']],
                ['text' => 'True or False: Conventional planning integrates environmental and economic objectives equally.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'Which city has committed to becoming carbon-neutral by 2025?', 'type' => 'fill_blank', 'answer' => 'Copenhagen'],
                ['text' => 'What has Singapore implemented to promote sustainability?', 'type' => 'multiple_choice', 'answer' => 'Green building regulations', 'options' => ['Cycling infrastructure', 'Green building regulations', 'Bus rapid transit', 'District heating systems']],
                ['text' => 'Which city is known for its bus rapid transit system?', 'type' => 'fill_blank', 'answer' => 'Curitiba'],
                ['text' => 'True or False: Curitiba is located in Argentina.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'According to research, how much return does sustainable infrastructure generate per dollar invested?', 'type' => 'multiple_choice', 'answer' => 'Approximately four dollars', 'options' => ['Approximately two dollars', 'Approximately three dollars', 'Approximately four dollars', 'Approximately five dollars']],
                ['text' => 'True or False: The lecturer argues that sustainable development is only an environmental issue.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'What does Copenhagen primarily invest in to achieve carbon neutrality?', 'type' => 'fill_blank', 'answer' => 'cycling infrastructure and district heating systems'],
            ],
        ]);

        $order = $this->createWriting($test, 'band7', $order, [
            ['type' => 'task1', 'prompt' => 'The line graph below shows changes in the percentage of households with internet access in four countries between 2005 and 2020. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.', 'instructions' => 'Write at least 150 words.', 'time' => 20, 'words' => 150],
            ['type' => 'task2', 'prompt' => 'Some people believe that universities should focus on providing students with academic knowledge, while others think universities should prepare students for employment. Discuss both views and give your own opinion.', 'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.', 'time' => 40, 'words' => 250],
        ]);

        $this->createSpeaking($test, 'band7', 'intermediate', $order, [
            'part1' => "1. What kind of work or studies are you currently involved in?\n2. How do you usually spend your free time?\n3. Do you think it is important to have hobbies?\n4. Has the way you spend your leisure time changed over the years?",
            'part2' => "Describe a person who has had a significant influence on your life.\nYou should say:\n- who this person is\n- how you know them\n- what influence they have had on you\nand explain why this person has been important to you.",
            'part3' => "1. How important is it for young people to have role models?\n2. Do you think the media portrays good role models for young people?\n3. How has the concept of success changed in modern society?\n4. What qualities do you think are most important for a leader to have?",
        ]);

        $this->command->line('  <fg=green>✓ Band 7 test created</>');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BAND 8 — Advanced: sophisticated vocabulary, nuanced arguments
    // ─────────────────────────────────────────────────────────────────────────

    private function seedBand8(): void
    {
        $this->command->line('  Creating Band 8 test...');

        $test = MockTest::create([
            'title'            => 'IELTS Mock Test 1 - BAND8',
            'description'      => 'Complete IELTS practice test 1 for band 7.5 level.',
            'band_level'       => 'band8',
            'duration_minutes' => 180,
            'is_active'        => true,
            'available_from'   => now(),
        ]);

        $order = 1;
        $order = $this->createReading($test, 'band8', 'advanced', $order, [
            'title'   => 'The Epistemology of Artificial Intelligence',
            'content' => 'The rapid proliferation of artificial intelligence systems across domains ranging from medical diagnosis to judicial decision-making has precipitated a fundamental epistemological crisis: how do we know what these systems know, and how do we evaluate the validity of their outputs? Unlike conventional software, whose operations can be traced through explicit logical pathways, contemporary machine learning models — particularly deep neural networks — operate through processes that remain largely opaque even to their creators. This phenomenon, commonly termed the "black box" problem, raises profound questions about accountability, transparency, and the nature of machine cognition itself.

Philosophers of mind have long debated whether computational systems can genuinely be said to "understand" the information they process, or whether they merely simulate understanding through sophisticated pattern recognition. John Searle\'s Chinese Room thought experiment, first proposed in 1980, remains a touchstone in this debate. Searle argued that a system could manipulate symbols according to rules and produce outputs indistinguishable from those of a genuine understanding agent, without possessing any semantic comprehension whatsoever. Contemporary large language models appear to instantiate precisely this scenario at unprecedented scale.

The practical implications of this epistemological uncertainty are considerable. In high-stakes domains such as healthcare, where AI diagnostic tools are increasingly deployed, the inability to interrogate the reasoning behind a system\'s recommendations creates significant clinical and ethical challenges. Regulatory frameworks in the European Union, most notably the proposed Artificial Intelligence Act, have attempted to address this through mandatory explainability requirements for high-risk AI applications. Critics, however, contend that enforcing explainability may fundamentally compromise model performance, creating an irresolvable tension between transparency and efficacy.

Recent advances in interpretability research — including techniques such as attention visualisation, saliency mapping, and concept activation vectors — have made partial progress in illuminating the internal representations of neural networks. Nevertheless, these methods provide post-hoc approximations rather than genuine mechanistic explanations, and their reliability remains contested within the research community. The epistemological challenge posed by AI systems thus represents not merely a technical problem awaiting a technical solution, but a fundamental philosophical question about the limits of human understanding in an increasingly automated world.',
            'questions' => [
                ['text' => 'What is the "black box" problem in AI?', 'type' => 'fill_blank', 'answer' => 'the opacity of machine learning models\' internal operations'],
                ['text' => 'When was Searle\'s Chinese Room thought experiment first proposed?', 'type' => 'multiple_choice', 'answer' => '1980', 'options' => ['1970', '1975', '1980', '1985']],
                ['text' => 'True or False: Searle argued that symbol manipulation necessarily implies semantic comprehension.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'What EU legislation addresses AI explainability requirements?', 'type' => 'fill_blank', 'answer' => 'the Artificial Intelligence Act'],
                ['text' => 'What tension does enforcing explainability create according to critics?', 'type' => 'multiple_choice', 'answer' => 'Between transparency and efficacy', 'options' => ['Between cost and performance', 'Between transparency and efficacy', 'Between speed and accuracy', 'Between regulation and innovation']],
                ['text' => 'True or False: Attention visualisation provides genuine mechanistic explanations of neural networks.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'Name one interpretability technique mentioned in the passage.', 'type' => 'multiple_choice', 'answer' => 'Saliency mapping', 'options' => ['Gradient descent', 'Saliency mapping', 'Backpropagation', 'Transfer learning']],
                ['text' => 'The author describes interpretability methods as providing ___ approximations.', 'type' => 'fill_blank', 'answer' => 'post-hoc'],
                ['text' => 'True or False: The epistemological challenge of AI is described as purely a technical problem.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'In which domain are AI diagnostic tools increasingly deployed?', 'type' => 'fill_blank', 'answer' => 'healthcare'],
            ],
        ]);

        $order = $this->createListening($test, 'band8', 'advanced', $order, [
            'title'    => 'Academic Panel: The Ethics of Genetic Engineering',
            'script'   => 'Moderator: Welcome to today\'s panel discussion on the ethics of genetic engineering. We have two distinguished academics joining us. Professor Martinez, you\'ve argued that germline editing represents an unprecedented ethical threshold. Could you elaborate?

Professor Martinez: Certainly. What distinguishes germline editing from somatic gene therapy is its heritable nature. Modifications made to germline cells — eggs, sperm, or early embryos — are transmitted to all subsequent generations. This creates what bioethicists call an intergenerational consent problem: we are making irreversible decisions that will affect individuals who cannot possibly consent to those decisions. The 2018 case of He Jiankui, who created the first gene-edited babies, demonstrated the catastrophic consequences of proceeding without adequate ethical oversight.

Dr. Chen: I would argue that the ethical calculus is more nuanced. The same logic that prohibits germline editing to prevent heritable diseases could be used to prohibit vaccination programmes that alter population-level immunity. We already make decisions that affect future generations through environmental policy, infrastructure investment, and educational systems. The question is not whether we should make such decisions, but how we should make them responsibly.

Professor Martinez: The analogy with vaccination is misleading. Vaccines do not alter the genome. Germline editing introduces permanent, heritable changes whose long-term consequences we cannot predict with any confidence. The precautionary principle demands that we exercise extreme caution when the potential for irreversible harm exists.

Dr. Chen: The precautionary principle, taken to its logical conclusion, would prohibit virtually all medical innovation. We must weigh the certain suffering caused by heritable genetic diseases against the speculative risks of germline intervention. For families affected by conditions such as Huntington\'s disease, the ethical imperative to act may outweigh the imperative to abstain.',
            'questions' => [
                ['text' => 'What distinguishes germline editing from somatic gene therapy?', 'type' => 'fill_blank', 'answer' => 'its heritable nature'],
                ['text' => 'What is the intergenerational consent problem?', 'type' => 'multiple_choice', 'answer' => 'Making irreversible decisions affecting people who cannot consent', 'options' => ['Getting consent from multiple generations simultaneously', 'Making irreversible decisions affecting people who cannot consent', 'Obtaining parental consent for genetic procedures', 'Sharing genetic data across generations']],
                ['text' => 'Who created the first gene-edited babies in 2018?', 'type' => 'fill_blank', 'answer' => 'He Jiankui'],
                ['text' => 'True or False: Dr. Chen argues that the ethical issues around germline editing are straightforward.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'What analogy does Dr. Chen use to challenge Professor Martinez\'s position?', 'type' => 'multiple_choice', 'answer' => 'Vaccination programmes', 'options' => ['Environmental policy', 'Vaccination programmes', 'Infrastructure investment', 'Educational systems']],
                ['text' => 'True or False: Professor Martinez agrees that the vaccination analogy is valid.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'What principle does Professor Martinez invoke to argue for caution?', 'type' => 'fill_blank', 'answer' => 'the precautionary principle'],
                ['text' => 'Which disease does Dr. Chen mention as an example of a heritable condition?', 'type' => 'multiple_choice', 'answer' => 'Huntington\'s disease', 'options' => ['Parkinson\'s disease', 'Alzheimer\'s disease', 'Huntington\'s disease', 'Cystic fibrosis']],
                ['text' => 'True or False: Both panellists agree that germline editing should be permanently prohibited.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'What does Dr. Chen say the precautionary principle would prohibit if taken to its logical conclusion?', 'type' => 'fill_blank', 'answer' => 'virtually all medical innovation'],
            ],
        ]);

        $order = $this->createWriting($test, 'band8', $order, [
            ['type' => 'task1', 'prompt' => 'The diagram below illustrates the process by which municipal solid waste is converted into energy through incineration. Summarise the information by selecting and reporting the main features.', 'instructions' => 'Write at least 150 words.', 'time' => 20, 'words' => 150],
            ['type' => 'task2', 'prompt' => 'Advances in technology have made it possible for people to work remotely from anywhere in the world. Some argue this development has more benefits than drawbacks, while others believe the opposite. Discuss both views and give your own opinion.', 'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.', 'time' => 40, 'words' => 250],
        ]);

        $this->createSpeaking($test, 'band8', 'advanced', $order, [
            'part1' => "1. How do you think technology has changed the way people communicate?\n2. Do you think people rely too heavily on technology in their daily lives?\n3. What technological development do you think has had the greatest impact on society?\n4. How do you think technology will change our lives in the next twenty years?",
            'part2' => "Describe a significant technological change you have witnessed in your lifetime.\nYou should say:\n- what the change was\n- when it occurred\n- how it affected people's lives\nand explain whether you consider this change to have been positive or negative overall.",
            'part3' => "1. To what extent do you think governments should regulate the development of new technologies?\n2. How do you think artificial intelligence will affect employment in the future?\n3. Do you believe that access to technology should be considered a fundamental human right?\n4. How can societies ensure that the benefits of technological advancement are distributed equitably?",
        ]);

        $this->command->line('  <fg=green>✓ Band 8 test created</>');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BAND 9 — Proficient: specialist vocabulary, dense academic argumentation
    // ─────────────────────────────────────────────────────────────────────────

    private function seedBand9(): void
    {
        $this->command->line('  Creating Band 9 test...');

        $test = MockTest::create([
            'title'            => 'IELTS Mock Test 1 - BAND9',
            'description'      => 'Complete IELTS practice test 1 for band 8 level.',
            'band_level'       => 'band9',
            'duration_minutes' => 180,
            'is_active'        => true,
            'available_from'   => now(),
        ]);

        $order = 1;
        $order = $this->createReading($test, 'band9', 'advanced', $order, [
            'title'   => 'Heterodox Economics and the Limits of Neoclassical Orthodoxy',
            'content' => 'The dominance of neoclassical economics within academic institutions and policy-making bodies has been periodically challenged by heterodox schools of thought that contest its foundational assumptions. The neoclassical paradigm, which posits rational utility-maximising agents operating within self-correcting markets, has provided the theoretical scaffolding for decades of economic policy. Yet its explanatory failures — most conspicuously its inability to anticipate the 2008 financial crisis — have reinvigorated debates about the adequacy of its methodological commitments and the insularity of its disciplinary boundaries.

Post-Keynesian economists, drawing on the work of Hyman Minsky, have argued that financial instability is endogenous to capitalist economies rather than the product of exogenous shocks. Minsky\'s financial instability hypothesis posits that periods of economic stability paradoxically generate the conditions for subsequent crises by encouraging excessive risk-taking and the accumulation of fragile debt structures. This insight, largely marginalised within mainstream economics prior to 2008, has since gained considerable traction among policymakers and central bankers grappling with the aftermath of the global financial crisis.

Institutional economics, associated with scholars such as Thorstein Veblen and John Kenneth Galbraith, offers a complementary critique by emphasising the role of social institutions, power relations, and cultural norms in shaping economic behaviour. Where neoclassical models abstract away from these contextual factors in pursuit of mathematical elegance, institutionalists argue that such abstraction renders economic models fundamentally inadequate as tools for understanding real-world economic dynamics.

The methodological debate between orthodox and heterodox economists reflects deeper epistemological disagreements about the nature of social science. Neoclassical economists have historically privileged mathematical formalisation and empirical falsifiability, drawing on the methodological norms of the natural sciences. Heterodox critics contend that this scientism misrepresents the ontological character of social phenomena, which are constituted by meaning, interpretation, and reflexivity in ways that resist reduction to mathematical formalism. The resolution of this debate has profound implications not merely for academic economics but for the policy frameworks that shape the distribution of resources and opportunities across societies.',
            'questions' => [
                ['text' => 'What does the neoclassical paradigm posit about economic agents?', 'type' => 'fill_blank', 'answer' => 'rational utility-maximising agents operating within self-correcting markets'],
                ['text' => 'What event most conspicuously exposed the failures of neoclassical economics?', 'type' => 'multiple_choice', 'answer' => 'The 2008 financial crisis', 'options' => ['The 1997 Asian financial crisis', 'The 2008 financial crisis', 'The 1929 Great Depression', 'The 2020 COVID recession']],
                ['text' => 'True or False: Minsky argued that financial instability is caused by external shocks to the economy.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'What does Minsky\'s hypothesis suggest about periods of economic stability?', 'type' => 'fill_blank', 'answer' => 'they generate the conditions for subsequent crises'],
                ['text' => 'Which scholars are associated with institutional economics?', 'type' => 'multiple_choice', 'answer' => 'Veblen and Galbraith', 'options' => ['Keynes and Friedman', 'Veblen and Galbraith', 'Marx and Engels', 'Hayek and Mises']],
                ['text' => 'True or False: Institutionalists argue that neoclassical abstraction makes economic models more accurate.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'What methodological approach have neoclassical economists historically privileged?', 'type' => 'fill_blank', 'answer' => 'mathematical formalisation and empirical falsifiability'],
                ['text' => 'What term describes the heterodox critique of neoclassical methodology?', 'type' => 'multiple_choice', 'answer' => 'Scientism', 'options' => ['Empiricism', 'Positivism', 'Scientism', 'Rationalism']],
                ['text' => 'True or False: The debate between orthodox and heterodox economists is purely technical with no policy implications.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'What three characteristics of social phenomena do heterodox critics argue resist mathematical formalism?', 'type' => 'fill_blank', 'answer' => 'meaning, interpretation, and reflexivity'],
            ],
        ]);

        $order = $this->createListening($test, 'band9', 'advanced', $order, [
            'title'    => 'Doctoral Colloquium: Postcolonial Approaches to International Development',
            'script'   => 'Supervisor: Today I want us to examine the epistemological foundations of international development discourse. The dominant development paradigm, as articulated through institutions like the World Bank and IMF, has been subjected to sustained critique from postcolonial theorists. Arturo Escobar\'s work is particularly instructive here. His concept of the "invention of development" argues that the post-war development apparatus constructed the so-called Third World as an object of knowledge and intervention, replicating colonial power relations under a modernisation framework.

Doctoral Student A: How does this relate to the concept of epistemic violence that Spivak discusses?

Supervisor: Excellent connection. Spivak\'s notion of epistemic violence refers to the ways in which dominant knowledge systems marginalise or render unintelligible the knowledge and experience of subaltern groups. In the development context, this manifests when Western technocratic expertise is privileged over indigenous knowledge systems, effectively silencing local voices in decisions that profoundly affect local communities. The imposition of structural adjustment programmes in the 1980s and 1990s exemplifies this dynamic — policies designed in Washington were implemented across vastly different social and economic contexts with minimal regard for local conditions.

Doctoral Student B: Is there a viable alternative framework, or is the critique purely deconstructive?

Supervisor: That\'s the central tension in postcolonial development studies. Scholars like Amartya Sen and Martha Nussbaum have attempted to construct normative frameworks — the capabilities approach being the most influential — that centre human agency and cultural diversity rather than imposing universal metrics of development. However, critics argue that even the capabilities approach, despite its apparent universalism, reflects particular philosophical traditions that may not translate across cultural contexts without imposing new forms of epistemic imperialism.',
            'questions' => [
                ['text' => 'What does Escobar\'s concept of the "invention of development" argue?', 'type' => 'fill_blank', 'answer' => 'that the post-war development apparatus constructed the Third World as an object of knowledge and intervention'],
                ['text' => 'Which institutions are cited as articulating the dominant development paradigm?', 'type' => 'multiple_choice', 'answer' => 'The World Bank and IMF', 'options' => ['The UN and WHO', 'The World Bank and IMF', 'The WTO and OECD', 'The EU and NATO']],
                ['text' => 'True or False: Spivak\'s epistemic violence refers to physical harm caused by colonial powers.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'What does epistemic violence manifest as in the development context?', 'type' => 'fill_blank', 'answer' => 'privileging Western technocratic expertise over indigenous knowledge systems'],
                ['text' => 'When were structural adjustment programmes primarily implemented?', 'type' => 'multiple_choice', 'answer' => 'The 1980s and 1990s', 'options' => ['The 1960s and 1970s', 'The 1980s and 1990s', 'The 1990s and 2000s', 'The 2000s and 2010s']],
                ['text' => 'True or False: The supervisor suggests that postcolonial critique offers only deconstruction with no alternative framework.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'What is the capabilities approach associated with?', 'type' => 'multiple_choice', 'answer' => 'Amartya Sen and Martha Nussbaum', 'options' => ['Escobar and Spivak', 'Amartya Sen and Martha Nussbaum', 'Keynes and Galbraith', 'Foucault and Derrida']],
                ['text' => 'What does the capabilities approach centre rather than imposing universal metrics?', 'type' => 'fill_blank', 'answer' => 'human agency and cultural diversity'],
                ['text' => 'True or False: Critics argue the capabilities approach is entirely free from cultural bias.', 'type' => 'true_false', 'answer' => 'FALSE', 'options' => ['TRUE', 'FALSE']],
                ['text' => 'What term describes the potential imposition of new knowledge hierarchies through even progressive frameworks?', 'type' => 'fill_blank', 'answer' => 'epistemic imperialism'],
            ],
        ]);

        $order = $this->createWriting($test, 'band9', $order, [
            ['type' => 'task1', 'prompt' => 'The table below presents data on income inequality, measured by the Gini coefficient, across six countries in 2000 and 2020. Summarise the information by selecting and reporting the main features, and make comparisons where relevant.', 'instructions' => 'Write at least 150 words.', 'time' => 20, 'words' => 150],
            ['type' => 'task2', 'prompt' => 'The increasing concentration of wealth among a small global elite poses a fundamental threat to democratic governance and social cohesion. To what extent do you agree or disagree with this statement?', 'instructions' => 'Give reasons for your answer and include any relevant examples from your own knowledge or experience. Write at least 250 words.', 'time' => 40, 'words' => 250],
        ]);

        $this->createSpeaking($test, 'band9', 'advanced', $order, [
            'part1' => "1. How do you think your educational background has shaped your worldview?\n2. To what extent do you think individuals can influence the societies they live in?\n3. How important is it to engage with perspectives that challenge your own beliefs?\n4. Do you think people today are more or less politically engaged than previous generations?",
            'part2' => "Describe a global issue that you feel strongly about.\nYou should say:\n- what the issue is\n- why it concerns you\n- what you think are its root causes\nand explain what you believe should be done to address it.",
            'part3' => "1. To what extent do you think international institutions are effective in addressing global challenges?\n2. How do you think the relationship between economic development and environmental sustainability should be managed?\n3. Do you believe that cultural diversity is under threat from globalisation, and if so, what should be done?\n4. How might the growing influence of non-state actors reshape international relations in the coming decades?",
        ]);

        $this->command->line('  <fg=green>✓ Band 9 test created</>');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Shared helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function createReading(MockTest $test, string $band, string $difficulty, int $order, array $data): int
    {
        $passage = ReadingPassage::create([
            'title'            => $data['title'],
            'content'          => $data['content'],
            'difficulty_level' => $difficulty,
            'band_level'       => $band,
            'time_limit'       => 20,
            'created_by'       => $this->adminId,
            'source'           => 'manual',
        ]);

        foreach ($data['questions'] as $q) {
            Question::create([
                'passage_id'       => $passage->id,
                'question_text'    => $q['text'],
                'question_type'    => $q['type'],
                'correct_answer'   => $q['answer'],
                'options'          => isset($q['options']) ? $q['options'] : null,
                'points'           => 1,
                'ielts_band_level' => str_replace('band', '', $band),
                'is_ai_generated'  => false,
            ]);
        }

        MockTestSection::create([
            'mock_test_id'     => $test->id,
            'module_type'      => 'reading',
            'content_id'       => $passage->id,
            'content_type'     => ReadingPassage::class,
            'order'            => $order,
            'duration_minutes' => 20,
        ]);

        return $order + 1;
    }

    private function createListening(MockTest $test, string $band, string $difficulty, int $order, array $data): int
    {
        $exercise = ListeningExercise::create([
            'title'            => $data['title'],
            'audio_file_path'  => 'manual/placeholder.mp3',
            'transcript'       => $data['script'],
            'duration'         => 300,
            'difficulty_level' => $difficulty,
            'band_level'       => $band,
            'created_by'       => $this->adminId,
            'source'           => 'manual',
        ]);

        foreach ($data['questions'] as $q) {
            ListeningQuestion::create([
                'listening_exercise_id' => $exercise->id,
                'question_text'         => $q['text'],
                'question_type'         => $q['type'],
                'correct_answer'        => $q['answer'],
                'options'               => isset($q['options']) ? $q['options'] : null,
                'points'                => 1,
            ]);
        }

        MockTestSection::create([
            'mock_test_id'     => $test->id,
            'module_type'      => 'listening',
            'content_id'       => $exercise->id,
            'content_type'     => ListeningExercise::class,
            'order'            => $order,
            'duration_minutes' => 30,
        ]);

        return $order + 1;
    }

    private function createWriting(MockTest $test, string $band, int $order, array $tasks): int
    {
        foreach ($tasks as $t) {
            $task = WritingTask::create([
                'title'        => 'Writing ' . strtoupper($t['type']) . ' - ' . strtoupper($band),
                'task_type'    => $t['type'],
                'prompt'       => $t['prompt'],
                'instructions' => $t['instructions'],
                'time_limit'   => $t['time'],
                'word_limit'   => $t['words'],
                'band_level'   => $band,
                'created_by'   => $this->adminId,
                'source'       => 'manual',
            ]);

            MockTestSection::create([
                'mock_test_id'     => $test->id,
                'module_type'      => 'writing',
                'content_id'       => $task->id,
                'content_type'     => WritingTask::class,
                'order'            => $order++,
                'duration_minutes' => $t['time'],
            ]);
        }

        return $order;
    }

    private function createSpeaking(MockTest $test, string $band, string $difficulty, int $order, array $parts): void
    {
        $config = [
            ['key' => 'part1', 'label' => 'Part 1', 'prep' => 0,  'resp' => 240, 'mins' => 4],
            ['key' => 'part2', 'label' => 'Part 2', 'prep' => 60, 'resp' => 120, 'mins' => 3],
            ['key' => 'part3', 'label' => 'Part 3', 'prep' => 0,  'resp' => 300, 'mins' => 5],
        ];

        foreach ($config as $c) {
            $prompt = SpeakingPrompt::create([
                'title'            => "Speaking {$c['label']} - " . strtoupper($band),
                'prompt_text'      => $parts[$c['key']],
                'preparation_time' => $c['prep'],
                'response_time'    => $c['resp'],
                'difficulty_level' => $difficulty,
                'band_level'       => $band,
                'created_by'       => $this->adminId,
                'source'           => 'manual',
            ]);

            MockTestSection::create([
                'mock_test_id'     => $test->id,
                'module_type'      => 'speaking',
                'content_id'       => $prompt->id,
                'content_type'     => SpeakingPrompt::class,
                'order'            => $order++,
                'duration_minutes' => $c['mins'],
            ]);
        }
    }
}
