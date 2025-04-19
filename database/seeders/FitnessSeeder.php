<?php
namespace Database\Seeders;

use App\Models\FitnessAssessmentSession;
use App\Models\FitnessTest;
use App\Models\FitnessTestThreshold;
use App\Models\StudentFitnessRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FitnessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create fitness tests
        $fitnessTests = $this->createFitnessTests();

        // Create assessment sessions
        $sessions = $this->createAssessmentSessions();

        // Create fitness records for students
        $this->createFitnessRecords($fitnessTests, $sessions);
    }

    /**
     * Create fitness tests with thresholds
     *
     * @return array
     */
    private function createFitnessTests(): array
    {
        // Military fitness test data
        $fitnessTestData = [
            [
                'name'             => 'Chay 100m',
                'unit'             => 'giay',
                'higher_is_better' => false,
                'thresholds'       => [
                    'excellent' => 13.0,
                    'good'      => 14.0,
                    'pass'      => 16.0,
                ],
            ],
            [
                'name'             => 'Chay 3000m',
                'unit'             => 'phut',
                'higher_is_better' => false,
                'thresholds'       => [
                    'excellent' => 12.0,
                    'good'      => 14.0,
                    'pass'      => 16.0,
                ],
            ],
            [
                'name'             => 'Hit xa',
                'unit'             => 'lan',
                'higher_is_better' => true,
                'thresholds'       => [
                    'excellent' => 20,
                    'good'      => 15,
                    'pass'      => 10,
                ],
            ],
            [
                'name'             => 'Boi 50m',
                'unit'             => 'giay',
                'higher_is_better' => false,
                'thresholds'       => [
                    'excellent' => 30,
                    'good'      => 40,
                    'pass'      => 50,
                ],
            ],
            [
                'name'             => 'Chong day',
                'unit'             => 'lan',
                'higher_is_better' => true,
                'thresholds'       => [
                    'excellent' => 50,
                    'good'      => 35,
                    'pass'      => 25,
                ],
            ],
            [
                'name'             => 'Leo day 5m',
                'unit'             => 'giay',
                'higher_is_better' => false,
                'thresholds'       => [
                    'excellent' => 8.0,
                    'good'      => 12.0,
                    'pass'      => 15.0,
                ],
            ],
        ];

        $fitnessTests = [];

        foreach ($fitnessTestData as $testData) {
            // Create the fitness test
            $test = FitnessTest::firstOrCreate(
                ['name' => $testData['name']],
                [
                    'unit'             => $testData['unit'],
                    'higher_is_better' => $testData['higher_is_better'],
                ]
            );

            // Create or update thresholds
            FitnessTestThreshold::updateOrCreate(
                ['fitness_test_id' => $test->id],
                [
                    'excellent_threshold' => $testData['thresholds']['excellent'],
                    'good_threshold'      => $testData['thresholds']['good'],
                    'pass_threshold'      => $testData['thresholds']['pass'],
                ]
            );

            $fitnessTests[] = $test;
        }

        return $fitnessTests;
    }

    /**
     * Create fitness assessment sessions
     *
     * @return array
     */
    private function createAssessmentSessions(): array
    {
        // Clear previous sessions created today
        FitnessAssessmentSession::whereDate('created_at', Carbon::today())->delete();

        $sessions = [];

        // Current week session
        $currentWeekSession = FitnessAssessmentSession::getCurrentWeekSession();
        $sessions[]         = $currentWeekSession;

        // Last week session
        $lastWeekSession = FitnessAssessmentSession::firstOrCreate(
            [
                'week_start_date' => Carbon::now()->subWeek()->startOfWeek(),
                'week_end_date'   => Carbon::now()->subWeek()->endOfWeek(),
            ],
            [
                'name'  => 'Danh gia tuan truoc',
                'notes' => 'Danh gia thuong ky',
            ]
        );
        $sessions[] = $lastWeekSession;

        // Last month session
        $lastMonthSession = FitnessAssessmentSession::firstOrCreate(
            [
                'week_start_date' => Carbon::now()->subMonth()->startOfWeek(),
                'week_end_date'   => Carbon::now()->subMonth()->endOfWeek(),
            ],
            [
                'name'  => 'Danh gia thang truoc',
                'notes' => 'Danh gia cuoi thang',
            ]
        );
        $sessions[] = $lastMonthSession;

        return $sessions;
    }

    /**
     * Create fitness records for students
     *
     * @param array $fitnessTests
     * @param array $sessions
     */
    private function createFitnessRecords(array $fitnessTests, array $sessions): void
    {
        // Clear existing records created today
        StudentFitnessRecord::whereDate('created_at', Carbon::today())->delete();

        // Get all students and managers
        $students = User::where('role', 'student')->get();
        $managers = User::where('role', 'manager')->get();

        if ($students->isEmpty() || $managers->isEmpty() || empty($fitnessTests)) {
            return;
        }

        foreach ($students as $student) {
            foreach ($sessions as $session) {
                // For each student in each session, create 3-5 fitness test records
                $testCount     = min(rand(3, 5), count($fitnessTests));
                $selectedTests = array_rand(array_flip(array_column($fitnessTests, 'id')), $testCount);

                if (! is_array($selectedTests)) {
                    $selectedTests = [$selectedTests];
                }

                foreach ($selectedTests as $testId) {
                    // Find test
                    $test = null;
                    foreach ($fitnessTests as $fitnessTest) {
                        if ($fitnessTest->id == $testId) {
                            $test = $fitnessTest;
                            break;
                        }
                    }

                    if (! $test) {
                        continue;
                    }

                    // Select random manager
                    $manager = $managers[array_rand($managers->toArray())];

                    // Create performance based on rating distribution (10% excellent, 30% good, 40% pass, 20% fail)
                    $ratingRoll = rand(1, 100);

                    if ($test->higher_is_better) {
                        // Higher is better (like push-ups)
                        if ($ratingRoll <= 10) {
                            // Excellent
                            $performance = $test->thresholds->excellent_threshold * (1 + rand(5, 20) / 100);
                        } elseif ($ratingRoll <= 40) {
                            // Good
                            $performance = $test->thresholds->good_threshold +
                            rand(1, 100) / 100 * ($test->thresholds->excellent_threshold - $test->thresholds->good_threshold);
                        } elseif ($ratingRoll <= 80) {
                            // Pass
                            $performance = $test->thresholds->pass_threshold +
                            rand(1, 100) / 100 * ($test->thresholds->good_threshold - $test->thresholds->pass_threshold);
                        } else {
                            // Fail
                            $performance = $test->thresholds->pass_threshold * (rand(50, 95) / 100);
                        }
                    } else {
                        // Lower is better (like running time)
                        if ($ratingRoll <= 10) {
                            // Excellent
                            $performance = $test->thresholds->excellent_threshold * (rand(80, 95) / 100);
                        } elseif ($ratingRoll <= 40) {
                            // Good
                            $performance = $test->thresholds->excellent_threshold +
                            rand(1, 100) / 100 * ($test->thresholds->good_threshold - $test->thresholds->excellent_threshold);
                        } elseif ($ratingRoll <= 80) {
                            // Pass
                            $performance = $test->thresholds->good_threshold +
                            rand(1, 100) / 100 * ($test->thresholds->pass_threshold - $test->thresholds->good_threshold);
                        } else {
                            // Fail
                            $performance = $test->thresholds->pass_threshold * (1 + rand(5, 20) / 100);
                        }
                    }

                    // Create the record
                    $record = new StudentFitnessRecord([
                        'user_id'               => $student->id,
                        'manager_id'            => $manager->id,
                        'fitness_test_id'       => $test->id,
                        'assessment_session_id' => $session->id,
                        'performance'           => round($performance, 2),
                        'notes'                 => 'Auto generated by seeder',
                    ]);

                    // Calculate rating and save
                    $record->calculateRating();
                    $record->save();
                }
            }
        }
    }
}
