<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ViolationRecord;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ViolationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createViolations();
    }
    
    /**
     * Create discipline violation records
     */
    private function createViolations(): void
    {
        // Get students and managers
        $students = User::where('role', 'student')->get();
        $managers = User::where('role', 'manager')->get();
        
        if ($students->isEmpty() || $managers->isEmpty()) {
            return;
        }
        
        // List of common violations in military academy
        $violations = [
            'Di hoc muon',
            'Vang mat khong phep',
            'Khong mac dong phuc',
            'Khong hoan thanh nhiem vu',
            'Vi pham noi quy',
            'Khong tham gia diem danh',
            'Su dung dien thoai trong gio hoc',
            'Khong tuan thu quy dinh an toan',
            'Khong chap hanh menh lenh',
            'Gay mat trat tu',
            'Trang phuc khong dung quy dinh',
            'Bao quan vu khi trang bi khong tot',
            'Bo vi tri khi dang gac'
        ];
        
        // Clear existing violations created today
        ViolationRecord::whereDate('created_at', Carbon::today())->delete();
        
        // Create random violations
        // 40% of students will have violations
        $violationCount = ceil($students->count() * 0.4);
        $selectedStudents = $students->random($violationCount);
        
        foreach ($selectedStudents as $student) {
            // Each student may have 1-3 violations
            $numViolations = rand(1, 3);
            
            for ($i = 0; $i < $numViolations; $i++) {
                // Pick random violation, manager, and date
                $violation = $violations[array_rand($violations)];
                $manager = $managers[array_rand($managers->toArray())];
                
                // Violations from last 2 months
                $date = Carbon::now()->subDays(rand(1, 60));
                
                ViolationRecord::create([
                    'student_id' => $student->id,
                    'manager_id' => $manager->id,
                    'violation_name' => $violation,
                    'violation_date' => $date
                ]);
            }
        }
    }
}
