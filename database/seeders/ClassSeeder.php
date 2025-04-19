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
        // Get all managers
        $managers     = User::where('role', 'manager')->get();
        $managerCount = $managers->count();

        if ($managerCount === 0) {
            // no manager -> exit
            return [];
        }

        $classes = [];
        foreach ($managers as $index => $manager) {
            $className = 'class_' . ($index + 1);
            $class = ClassRoom::firstOrCreate(
                ['name' => $className],
                ['manager_id' => $manager->id]
            );
            // Update management_unit for manager
            $managerDetail = \App\Models\ManagerDetail::where('user_id', $manager->id)->first();
            if ($managerDetail) {
                $managerDetail->management_unit = $class->id;
                $managerDetail->save();
            }
            $classes[] = $class;
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

        // Get all students and shuffle
        $students = User::where('role', 'student')->get()->shuffle();
        $studentIds = $students->pluck('id')->toArray();

        $remainingStudentIds = $studentIds;
        $minPerClass = 2;
        $maxPerClass = max(2, floor(count($studentIds) / max(count($classes), 1)) + 2);

        foreach ($classes as $class) {
            if (count($remainingStudentIds) < $minPerClass) {
                // Assign all remaining students to the last class
                $assignedIds = $remainingStudentIds;
                $remainingStudentIds = [];
            } else {
                // Random số lượng student cho lớp này (tối thiểu 2, tối đa số còn lại hoặc $maxPerClass)
                $maxForThisClass = min($maxPerClass, count($remainingStudentIds));
                $numToAssign = rand($minPerClass, $maxForThisClass);
                $assignedIds = array_splice($remainingStudentIds, 0, $numToAssign);
            }

            $roles = array_fill(0, count($assignedIds), 'student');
            if (count($assignedIds) > 2) {
                // Random 1 lớp trưởng, 1 lớp phó
                $monitorIdx = array_rand($assignedIds);
                do {
                    $viceMonitorIdx = array_rand($assignedIds);
                } while ($viceMonitorIdx === $monitorIdx);

                $roles[$monitorIdx] = 'monitor';
                $roles[$viceMonitorIdx] = 'vice_monitor';
            }

            foreach ($assignedIds as $i => $studentId) {
                // Random status (mostly active)
                $statuses = ['active', 'active', 'active', 'active', 'suspended'];
                $status   = $statuses[array_rand($statuses)];

                StudentClass::create([
                    'class_id' => $class->id,
                    'user_id'  => $studentId,
                    'role'     => $roles[$i],
                    'status'   => $status,
                    'note'     => "Auto assigned by seeder - {$roles[$i]}",
                ]);
            }
        }
    }
}
