<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\StudentClass;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create classes
        $classes = $this->createClasses();
        
        // Assign students to classes
        $this->assignStudents($classes);
    }
    
    /**
     * Create classes with assigned managers
     * 
     * @return array
     */
    private function createClasses(): array
    {
        $classNames = [
            'Dai Doi 1 - C101',
            'Dai Doi 1 - C102',
            'Dai Doi 2 - C201',
            'Dai Doi 2 - C202',
            'Dai Doi 3 - C301',
            'Tieu Doi CNTT1',
            'Tieu Doi CNTT2',
            'Tieu Doi ATTT1'
        ];
        
        // Get all managers
        $managers = User::where('role', 'manager')->get();
        $managerCount = $managers->count();
        
        if ($managerCount === 0) {
            // If no managers found, create a default one
            $manager = User::firstOrCreate(
                ['email' => 'manager_default@military.edu.vn'],
                [
                    'name' => 'Manager_Default',
                    'password' => bcrypt('123456'),
                    'role' => 'manager'
                ]
            );
            $managers = [$manager];
            $managerCount = 1;
        }
        
        $classes = [];
        foreach ($classNames as $index => $className) {
            $managerIndex = $index % $managerCount;
            $classes[] = ClassRoom::firstOrCreate(
                ['name' => $className],
                ['manager_id' => $managers[$managerIndex]->id]
            );
        }
        
        return $classes;
    }
    
    /**
     * Assign students to classes with different roles
     * 
     * @param array $classes
     */
    private function assignStudents(array $classes): void
    {
        // Clear existing student class assignments
        StudentClass::truncate();
        
        // Get all students
        $students = User::where('role', 'student')->get();
        $studentCount = $students->count();
        
        if ($studentCount > 0) {
            $classCount = count($classes);
            
            foreach ($students as $index => $student) {
                $classIndex = $index % $classCount;
                
                // Determine student role in class
                $role = 'student';
                $studentsPerClass = ceil($studentCount / $classCount);
                $positionInClass = floor($index / $classCount);
                
                if ($positionInClass === 0) {
                    $role = 'monitor'; // First student in each class is monitor
                } elseif ($positionInClass === 1) {
                    $role = 'vice_monitor'; // Second student is vice monitor
                }
                
                // Random status (mostly active) - only use values defined in StudentClass::STATUSES
                $statuses = ['active', 'active', 'active', 'active', 'suspended'];
                $status = $statuses[array_rand($statuses)];
                
                StudentClass::create([
                    'class_id' => $classes[$classIndex]->id,
                    'user_id' => $student->id,
                    'role' => $role,
                    'status' => $status,
                    'note' => "Auto assigned by seeder - {$role}",
                ]);
            }
        }
    }
}
