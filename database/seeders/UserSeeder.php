<?php
namespace Database\Seeders;

use App\Models\User;
use App\Models\StudentDetail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin accounts
        $this->createAdmins();

        // Create manager accounts
        $this->createManagers();

        // Create student accounts
        $this->createStudents();

        // Create student details for each student
        $this->createStudentDetails();
    }

    /**
     * Create admin accounts
     */
    private function createAdmins(): void
    {
        // Create 3 more admin accounts
        for ($i = 2; $i <= 4; $i++) {
            User::firstOrCreate(
                ['email' => "admin_{$i}@gmail.com"],
                [
                    'name'           => "admin #{$i}",
                    'password'       => Hash::make('123456'),
                    'role'           => 'admin',
                    'remember_token' => Str::random(10),
                ]
            );
        }
    }

    /**
     * Create manager accounts with their details
     */
    private function createManagers(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $manager = User::firstOrCreate(
                ['email' => "manager_{$i}@gmail.com"],
                [
                    'name'           => "manager #{$i}",
                    'password'       => Hash::make('123456'),
                    'role'           => 'manager',
                    'remember_token' => Str::random(10),
                ]
            );
        }
    }

    /**
     * Create student accounts
     */
    private function createStudents(): void
    {
        // Create 30 student accounts
        for ($i = 1; $i <= 30; $i++) {
            User::firstOrCreate(
                ['email' => "student_{$i}@gmail.com"],
                [
                    'name'           => "student #{$i}",
                    'password'       => Hash::make('123456'),
                    'role'           => 'student',
                    'remember_token' => Str::random(10),
                ]
            );
        }
    }

    /**
     * Create student details for each student
     */
    private function createStudentDetails(): void
    {
        $students = User::where('role', 'student')->get();
        foreach ($students as $i => $student) {
            StudentDetail::firstOrCreate(
                ['user_id' => $student->id],
                [
                    'date_of_birth' => now()->subYears(18 + ($i % 5))->format('Y-m-d'),
                    'rank' => 'Binh nhì',
                    'place_of_origin' => 'Tỉnh ' . chr(65 + ($i % 5)),
                    'working_unit' => 'Đơn vị ' . (($i % 3) + 1),
                    'year_of_study' => ($i % 4) + 1,
                    'political_status' => ['party_member', 'youth_union_member', 'none'][$i % 3],
                    'phone_number' => '09000000' . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                    'permanent_residence' => 'Huyện ' . chr(65 + ($i % 5)) . ', Tỉnh ' . chr(65 + ($i % 5)),
                    // Father
                    'father_name' => 'Nguyễn Văn ' . chr(65 + ($i % 5)),
                    'father_birth_year' => 1970 + ($i % 10),
                    'father_phone_number' => '09110000' . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                    'father_place_of_origin' => 'Tỉnh ' . chr(65 + ($i % 5)),
                    'father_occupation' => 'Nông dân',
                    // Mother
                    'mother_name' => 'Trần Thị ' . chr(65 + ($i % 5)),
                    'mother_birth_year' => 1972 + ($i % 10),
                    'mother_phone_number' => '09120000' . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                    'mother_place_of_origin' => 'Tỉnh ' . chr(65 + ($i % 5)),
                    'mother_occupation' => 'Giáo viên',
                ]
            );
        }
    }
}
