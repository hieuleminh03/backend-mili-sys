<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Course;
use App\Models\ManagerDetail;
use App\Models\StudentClass;
use App\Models\Term;
use App\Models\User;
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
        
        // Danh sách tên học viên
        $studentNames = ['Hiếu', 'Văn', 'Hoàng', 'Trung', 'Tú', 'Quỳnh', 'Nam', 'Tâm', 'Vân', 'Uyên'];
        
        // Tạo 10 tài khoản học viên
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
        
        // Tạo 3 tài khoản quản lý
        $managerNames = ['Viruss', 'Ngọc Kem', 'Pháo'];
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
            
            // Tạo manager detail
            ManagerDetail::firstOrCreate(
                ['user_id' => $manager->id],
                ['management_unit' => null]
            );
            
            $managers[] = $manager;
        }
        
        // Tạo học kỳ
        $term = Term::create([
            'name' => date('Y') . 'A',
            'start_date' => Carbon::now()->subMonths(1),
            'end_date' => Carbon::now()->addMonths(4),
            'roster_deadline' => Carbon::now()->addMonths(1),
            'grade_entry_date' => Carbon::now()->addMonths(5)
        ]);
        
        // Tạo 2 khóa học
        $courseNames = ['IT2130', 'IT4190'];
        $courses = [];
        
        foreach ($courseNames as $index => $code) {
            $courses[] = Course::create([
                'code' => $code,
                'subject_name' => 'Môn học ' . $code,
                'term_id' => $term->id,
                'enroll_limit' => rand(30, 50),
                'midterm_weight' => 0.4
            ]);
        }
        
        // Tạo 2 lớp học
        $classes = [];
        
        for ($i = 0; $i < 2; $i++) {
            $classes[] = ClassRoom::create([
                'name' => 'Lớp ' . Str::random(5),
                'manager_id' => $managers[$i]->id
            ]);
        }
        
        // Phân bổ học viên vào các lớp
        foreach ($students as $index => $student) {
            $classIndex = $index % 2; // Phân nửa vào lớp 1, nửa vào lớp 2
            
            $role = 'student';
            if ($index === 0) {
                $role = 'monitor'; // Học viên đầu tiên của mỗi lớp làm lớp trưởng
            } elseif ($index === 1 || $index === 2) {
                $role = 'vice_monitor'; // Học viên thứ 2,3 làm lớp phó
            }
            
            StudentClass::create([
                'class_id' => $classes[$classIndex]->id,
                'user_id' => $student->id,
                'role' => $role,
                'status' => 'active',
                'note' => 'Tạo bởi seeder'
            ]);
        }
    }
}
