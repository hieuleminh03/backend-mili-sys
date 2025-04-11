<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Course;
use App\Models\FitnessAssessmentSession;
use App\Models\FitnessTest;
use App\Models\FitnessTestThreshold;
use App\Models\ManagerDetail;
use App\Models\MilitaryEquipmentType;
use App\Models\MonthlyAllowance;
use App\Models\StudentClass;
use App\Models\StudentCourse;
use App\Models\StudentEquipmentReceipt;
use App\Models\StudentFitnessRecord;
use App\Models\Term;
use App\Models\User;
use App\Models\ViolationRecord;
use App\Models\YearlyEquipmentDistribution;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * fill data vào database
     */
    public function run(): void
    {
        // Tạo tài khoản admin
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@military.edu.vn',
            'password' => Hash::make('123456'),
            'role' => 'admin',
            'remember_token' => Str::random(10),
        ]);

        // Danh sách tên học viên - Thêm nhiều tên hơn
        $studentNames = [
            'Hiếu', 'Văn', 'Hoàng', 'Trung', 'Tú', 'Quỳnh', 'Nam', 'Tâm', 'Vân', 'Uyên',
            'Dũng', 'Minh', 'Hùng', 'Thảo', 'Linh', 'Phương', 'Bình', 'Đức', 'Hải', 'Long',
            'Tuấn', 'Cường', 'Ngọc', 'Hương', 'Mai', 'Anh', 'Hà', 'Thu', 'Hạnh', 'Kiên'
        ];

        // Tạo 30 tài khoản học viên
        $students = [];
        foreach ($studentNames as $name) {
            $email = strtolower($name) . '_' . Str::random(5) . '@student.edu.vn';
            $students[] = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('123456'),
                'role' => 'student',
                'remember_token' => Str::random(10),
            ]);
        }

        // Tạo 5 tài khoản quản lý
        $managerNames = ['Viruss', 'Ngọc Kem', 'Pháo', 'Tùng Sơn', 'Cường Seven'];
        $managers = [];
        foreach ($managerNames as $name) {
            $email = strtolower(str_replace(' ', '', $name)) . '_' . Str::random(5) . '@manager.edu.vn';
            $manager = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('123456'),
                'role' => 'manager',
                'remember_token' => Str::random(10),
            ]);

            // Tạo manager detail với đơn vị quản lý
            $units = ['Tiểu đoàn 1', 'Tiểu đoàn 2', 'Tiểu đoàn 3', 'Đại đội 1', 'Đại đội 2'];
            ManagerDetail::firstOrCreate(
                ['user_id' => $manager->id],
                ['management_unit' => $units[array_rand($units)]]
            );

            $managers[] = $manager;
        }

        // Tạo học kỳ hiện tại và học kỳ tiếp theo
        $currentTerm = Term::create([
            'name' => date('Y') . 'A',
            'start_date' => Carbon::now()->subMonths(1),
            'end_date' => Carbon::now()->addMonths(4),
            'roster_deadline' => Carbon::now()->addMonths(1),
            'grade_entry_date' => Carbon::now()->addMonths(5),
        ]);

        $nextTerm = Term::create([
            'name' => date('Y') . 'B',
            'start_date' => Carbon::now()->addMonths(5),
            'end_date' => Carbon::now()->addMonths(10),
            'roster_deadline' => Carbon::now()->addMonths(6),
            'grade_entry_date' => Carbon::now()->addMonths(11),
        ]);

        $terms = [$currentTerm, $nextTerm];

        // Tạo khóa học cho cả hai học kỳ
        $courseNames = [
            'IT2130' => 'Lập trình cơ bản',
            'IT4190' => 'Nhập môn trí tuệ nhân tạo',
            'MI1010' => 'Giáo dục quốc phòng',
            'MI2020' => 'Chiến thuật quân sự',
            'MI3030' => 'Huấn luyện quân sự',
            'PE2010' => 'Giáo dục thể chất',
        ];
        
        $courses = [];
        foreach ($terms as $termIndex => $term) {
            $termSuffix = $termIndex === 0 ? 'A' : 'B'; // Thêm hậu tố cho mã khóa học dựa vào học kỳ
            
            foreach ($courseNames as $code => $name) {
                // Tạo mã khóa học duy nhất bằng cách thêm hậu tố học kỳ
                $uniqueCode = substr($code, 0, 4) . $termSuffix;
                
                $courses[] = Course::create([
                    'code' => $uniqueCode,
                    'subject_name' => $name,
                    'term_id' => $term->id,
                    'enroll_limit' => rand(30, 50),
                    'midterm_weight' => 0.4,
                ]);
            }
        }

        // Tạo lớp học
        $classes = [];
        $classNames = ['Lớp QTKD1', 'Lớp CNTT1', 'Lớp ATTT1', 'Lớp QLNN1', 'Lớp ĐTVT1'];

        foreach ($classNames as $index => $className) {
            $managerIndex = $index % count($managers);
            $classes[] = ClassRoom::create([
                'name' => $className,
                'manager_id' => $managers[$managerIndex]->id,
            ]);
        }

        // Phân bổ học viên vào các lớp
        foreach ($students as $index => $student) {
            $classIndex = $index % count($classes);
            
            $role = 'student';
            if ($index % count($classes) === 0) {
                $role = 'monitor'; // Lớp trưởng
            } elseif ($index % count($classes) === 1 || $index % count($classes) === 2) {
                $role = 'vice_monitor'; // Lớp phó
            }

            StudentClass::create([
                'class_id' => $classes[$classIndex]->id,
                'user_id' => $student->id,
                'role' => $role,
                'status' => 'active',
                'note' => 'Tạo bởi seeder',
            ]);
        }

        // Đăng ký học viên vào các khóa học
        foreach ($students as $student) {
            // Chọn ngẫu nhiên 3 khóa học cho mỗi học viên
            $randomCourses = array_rand(array_flip(array_column($courses, 'id')), 3);
            
            foreach ($randomCourses as $courseId) {
                $midtermGrade = rand(6, 10);
                $finalGrade = rand(6, 10);
                $totalGrade = $midtermGrade * 0.4 + $finalGrade * 0.6;
                
                StudentCourse::create([
                    'user_id' => $student->id,
                    'course_id' => $courseId,
                    'status' => 'enrolled',
                    'midterm_grade' => $midtermGrade,
                    'final_grade' => $finalGrade,
                    'total_grade' => $totalGrade,
                ]);
            }
        }

        // Tạo các bài kiểm tra thể lực
        $fitnessTests = [
            [
                'name' => 'Chạy 100m',
                'unit' => 'giây',
                'higher_is_better' => false,
                'thresholds' => [
                    'excellent' => 13.0,
                    'good' => 14.0,
                    'pass' => 16.0,
                ]
            ],
            [
                'name' => 'Chạy 3000m',
                'unit' => 'phút',
                'higher_is_better' => false,
                'thresholds' => [
                    'excellent' => 12.0,
                    'good' => 14.0,
                    'pass' => 16.0,
                ]
            ],
            [
                'name' => 'Hít xà 10 phút',
                'unit' => 'lần',
                'higher_is_better' => true,
                'thresholds' => [
                    'excellent' => 20,
                    'good' => 15,
                    'pass' => 10,
                ]
            ],
            [
                'name' => 'Bơi 50m',
                'unit' => 'giây',
                'higher_is_better' => false,
                'thresholds' => [
                    'excellent' => 30,
                    'good' => 40,
                    'pass' => 50,
                ]
            ],
            [
                'name' => 'Chống đẩy',
                'unit' => 'lần',
                'higher_is_better' => true,
                'thresholds' => [
                    'excellent' => 50,
                    'good' => 35,
                    'pass' => 25,
                ]
            ],
        ];

        $createdTests = [];
        foreach ($fitnessTests as $testData) {
            $test = FitnessTest::create([
                'name' => $testData['name'],
                'unit' => $testData['unit'],
                'higher_is_better' => $testData['higher_is_better'],
            ]);

            FitnessTestThreshold::create([
                'fitness_test_id' => $test->id,
                'excellent_threshold' => $testData['thresholds']['excellent'],
                'good_threshold' => $testData['thresholds']['good'],
                'pass_threshold' => $testData['thresholds']['pass'],
            ]);

            $createdTests[] = $test;
        }

        // Tạo phiên đánh giá thể lực
        $assessmentSessions = [];
        
        // Phiên đánh giá tuần hiện tại
        $currentWeekSession = FitnessAssessmentSession::getCurrentWeekSession();
        $assessmentSessions[] = $currentWeekSession;
        
        // Phiên đánh giá tuần trước
        $lastWeekSession = FitnessAssessmentSession::create([
            'name' => 'Đánh giá tuần trước',
            'week_start_date' => Carbon::now()->subWeek()->startOfWeek(),
            'week_end_date' => Carbon::now()->subWeek()->endOfWeek(),
            'notes' => 'Đánh giá thường kỳ',
        ]);
        $assessmentSessions[] = $lastWeekSession;

        // Thêm kết quả đánh giá thể lực cho học viên
        foreach ($students as $student) {
            foreach ($assessmentSessions as $session) {
                // Chọn 3 bài test ngẫu nhiên cho mỗi học viên
                $randomTests = array_rand(array_flip(array_column($createdTests, 'id')), 3);
                
                foreach ($randomTests as $testId) {
                    $test = $createdTests[array_search($testId, array_column($createdTests, 'id'))];
                    $randomManager = $managers[array_rand($managers)];
                    
                    // Tạo kết quả ngẫu nhiên phù hợp với bài test
                    if ($test->higher_is_better) {
                        // Càng cao càng tốt
                        $minThreshold = $test->thresholds->pass_threshold * 0.7; // Cho phép cả điểm yếu
                        $maxThreshold = $test->thresholds->excellent_threshold * 1.2;
                        $performance = rand($minThreshold * 100, $maxThreshold * 100) / 100;
                    } else {
                        // Càng thấp càng tốt
                        $minThreshold = $test->thresholds->excellent_threshold * 0.8;
                        $maxThreshold = $test->thresholds->pass_threshold * 1.2; // Cho phép cả điểm yếu
                        $performance = rand($minThreshold * 100, $maxThreshold * 100) / 100;
                    }
                    
                    $record = StudentFitnessRecord::create([
                        'user_id' => $student->id,
                        'manager_id' => $randomManager->id,
                        'fitness_test_id' => $test->id,
                        'assessment_session_id' => $session->id,
                        'performance' => $performance,
                        'notes' => 'Đánh giá tự động',
                    ]);
                    
                    // Tính toán và cập nhật xếp loại
                    $record->calculateRating();
                    $record->save();
                }
            }
        }

        // Tạo vi phạm cho một số học viên ngẫu nhiên
        $violations = [
            'Đi học muộn',
            'Vắng mặt không phép',
            'Không mặc đồng phục',
            'Không hoàn thành nhiệm vụ',
            'Vi phạm nội quy',
            'Không tham gia điểm danh',
            'Sử dụng điện thoại trong giờ học',
            'Không tuân thủ quy định an toàn',
        ];

        // Tạo 20 vi phạm ngẫu nhiên
        for ($i = 0; $i < 20; $i++) {
            $randomStudent = $students[array_rand($students)];
            $randomManager = $managers[array_rand($managers)];
            $randomViolation = $violations[array_rand($violations)];
            $randomDate = Carbon::now()->subDays(rand(1, 30));

            ViolationRecord::create([
                'student_id' => $randomStudent->id,
                'manager_id' => $randomManager->id,
                'violation_name' => $randomViolation,
                'violation_date' => $randomDate,
            ]);
        }

        // Tạo loại trang bị quân sự
        $equipmentTypes = [
            ['name' => 'Quân phục', 'description' => 'Quân phục thường ngày'],
            ['name' => 'Mũ bảo hiểm', 'description' => 'Mũ bảo hiểm quân đội tiêu chuẩn'],
            ['name' => 'Giày combat', 'description' => 'Giày chiến đấu quân sự'],
            ['name' => 'Ba lô quân đội', 'description' => 'Ba lô đựng dụng cụ quân sự'],
            ['name' => 'Áo mưa', 'description' => 'Áo mưa quân đội'],
            ['name' => 'Đèn pin', 'description' => 'Đèn pin chiến thuật'],
            ['name' => 'Bình nước', 'description' => 'Bình đựng nước tiêu chuẩn quân đội'],
        ];

        $createdEquipmentTypes = [];
        foreach ($equipmentTypes as $type) {
            $createdEquipmentTypes[] = MilitaryEquipmentType::create([
                'name' => $type['name'],
                'description' => $type['description'],
            ]);
        }

        // Tạo phân phối trang bị hàng năm
        $currentYear = date('Y');
        $distributions = [];
        
        foreach ($createdEquipmentTypes as $type) {
            $distributions[] = YearlyEquipmentDistribution::create([
                'equipment_type_id' => $type->id,
                'year' => $currentYear,
                'quantity' => rand(30, 100), // Tổng số lượng trang bị được phân phối
            ]);
        }

        // Tạo phiếu nhận trang bị cho học viên
        foreach ($students as $student) {
            // Mỗi học viên nhận 3-5 loại trang bị ngẫu nhiên
            $randomDistributions = array_rand(array_flip(array_column($distributions, 'id')), rand(3, 5));
            
            foreach ($randomDistributions as $distributionId) {
                $distribution = $distributions[array_search($distributionId, array_column($distributions, 'id'))];
                
                // Một số học viên đã nhận trang bị, một số chưa
                $received = rand(0, 10) > 3; // 70% đã nhận
                $receivedAt = $received ? Carbon::now()->subDays(rand(1, 30)) : null;
                
                StudentEquipmentReceipt::create([
                    'user_id' => $student->id,
                    'distribution_id' => $distribution->id,
                    'received' => $received,
                    'received_at' => $receivedAt,
                    'notes' => $received ? 'Đã nhận đầy đủ trang bị' : 'Chưa nhận trang bị',
                ]);
            }
        }

        // Tạo trợ cấp hàng tháng cho học viên
        for ($month = 1; $month <= 4; $month++) {
            foreach ($students as $student) {
                // Tính số tiền trợ cấp (từ 500,000 đến 2,000,000 VND)
                $amount = rand(5, 20) * 100000;
                
                // Đánh dấu 'received' cho các tháng trước, chưa nhận cho tháng hiện tại
                $received = $month < date('n');
                $receivedAt = $received ? Carbon::create($currentYear, $month, rand(10, 25)) : null;
                
                MonthlyAllowance::create([
                    'user_id' => $student->id,
                    'month' => $month,
                    'year' => $currentYear,
                    'amount' => $amount,
                    'received' => $received,
                    'received_at' => $receivedAt,
                    'notes' => $received ? 'Đã nhận đầy đủ' : 'Chưa nhận',
                ]);
            }
        }
    }
}
