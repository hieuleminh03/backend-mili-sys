<?php
namespace Database\Seeders;

use App\Models\User;
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
}
