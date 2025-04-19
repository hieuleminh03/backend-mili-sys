<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\StudentCourse;
use App\Models\Term;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create courses for each term
        $courses = $this->createCourses();
        
        // Enroll students in courses
        $this->enrollStudents($courses);
    }
    
    /**
     * Create courses for all terms
     * 
     * @return array
     */
    private function createCourses(): array
    {
        // Military course data
        $courseData = [
            'IT2130' => 'Lap trinh co ban',
            'IT4190' => 'Nhap mon tri tue nhan tao',
            'MI1010' => 'Giao duc quoc phong co ban',
            'MI2020' => 'Chien thuat quan su',
            'MI3030' => 'Huan luyen quan su nang cao',
            'PE2010' => 'Giao duc the chat',
            'MI1101' => 'Lich su quan su',
            'MI2201' => 'Ky thuat chien dau',
            'MI3301' => 'Dieu lenh quan ly bo doi',
            'IT2202' => 'An ninh mang co ban'
        ];
        
        // Get all terms
        $terms = Term::all();
        $courses = [];
        
        foreach ($terms as $termIndex => $term) {
            $termSuffix = chr(65 + $termIndex); // A, B, C, D...

            $random_weights = [0.3, 0.4, 0.5];
            
            foreach ($courseData as $code => $name) {
                $uniqueCode = substr($code, 0, 4) . $termSuffix;
                $midtermWeight = $random_weights[array_rand($random_weights)];
                $enrollLimit = mt_rand(30, 60);
                
                $courses[] = Course::firstOrCreate(
                    ['code' => $uniqueCode],
                    [
                        'subject_name' => $name,
                        'term_id' => $term->id,
                        'enroll_limit' => $enrollLimit,
                        'midterm_weight' => $midtermWeight,
                    ]
                );
            }
        }
        
        return $courses;
    }
    
    /**
     * Enroll students in courses with grades
     * 
     * @param array $courses
     */
    private function enrollStudents(array $courses): void
    {
        // Clear existing enrollments
        StudentCourse::truncate();
        
        // Get all students
        $students = User::where('role', 'student')->get();
        
        if ($students->count() === 0) {
            return;
        }
        
        // Group courses by term
        $coursesByTerm = [];
        foreach ($courses as $course) {
            if (!isset($coursesByTerm[$course->term_id])) {
                $coursesByTerm[$course->term_id] = [];
            }
            $coursesByTerm[$course->term_id][] = $course;
        }
        
        // Get current term
        $currentYear = date('Y');
        $currentTermName = $currentYear . 'A'; // Assuming first half of year
        $currentTerm = Term::where('name', $currentTermName)->first();
        
        // If current term not found, use the latest term
        if (!$currentTerm) {
            $currentTerm = Term::orderBy('start_date', 'desc')->first();
        }
        
        // Set past term (term before current)
        $pastTerm = Term::where('end_date', '<', $currentTerm->start_date)
                        ->orderBy('end_date', 'desc')
                        ->first();
        
        foreach ($students as $student) {
            // Enroll in current term courses (3-6 courses)
            if (isset($coursesByTerm[$currentTerm->id])) {
                $currentTermCourses = $coursesByTerm[$currentTerm->id];
                $coursesToEnroll = min(rand(3, 6), count($currentTermCourses));
                
                // Shuffle and take a subset
                shuffle($currentTermCourses);
                $selectedCourses = array_slice($currentTermCourses, 0, $coursesToEnroll);
                
                foreach ($selectedCourses as $course) {
                    // Current term: only midterm grades, some pending finals
                    $midtermGrade = rand(40, 100) / 10; // 4.0 to 10.0
                    $finalGrade = (rand(0, 100) < 30) ? rand(40, 100) / 10 : null; // 30% have final grades
                    $totalGrade = null;
                    
                    if ($finalGrade !== null) {
                        $totalGrade = $course->calculateTotalGrade($midtermGrade, $finalGrade);
                    }
                    
                    StudentCourse::create([
                        'user_id' => $student->id,
                        'course_id' => $course->id,
                        'status' => 'enrolled',
                        'midterm_grade' => $midtermGrade,
                        'final_grade' => $finalGrade,
                        'total_grade' => $totalGrade,
                        'notes' => 'Current term enrollment',
                    ]);
                }
            }
            
            // Enroll in past term courses (4-7 courses) with complete grades
            if ($pastTerm && isset($coursesByTerm[$pastTerm->id])) {
                $pastTermCourses = $coursesByTerm[$pastTerm->id];
                $coursesToEnroll = min(rand(4, 7), count($pastTermCourses));
                
                // Shuffle and take a subset
                shuffle($pastTermCourses);
                $selectedCourses = array_slice($pastTermCourses, 0, $coursesToEnroll);
                
                foreach ($selectedCourses as $course) {
                    // Past term: all grades are finalized
                    $midtermGrade = rand(40, 100) / 10; // 4.0 to 10.0
                    $finalGrade = rand(40, 100) / 10; // 4.0 to 10.0
                    $totalGrade = $course->calculateTotalGrade($midtermGrade, $finalGrade);
                    
                    // Determine status based on grade
                    $status = 'completed';
                    if ($totalGrade < 5.0) {
                        $status = 'failed';
                    } elseif (rand(1, 100) <= 5) { // 5% chance of dropping
                        $status = 'dropped';
                        $finalGrade = null;
                        $totalGrade = null;
                    }
                    
                    StudentCourse::create([
                        'user_id' => $student->id,
                        'course_id' => $course->id,
                        'status' => $status,
                        'midterm_grade' => $midtermGrade,
                        'final_grade' => $finalGrade,
                        'total_grade' => $totalGrade,
                        'notes' => "Past term - {$status}",
                    ]);
                }
            }
        }
    }
}
