<?php

namespace Database\Seeders;

use App\Models\ManagerDetail;
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
        // Create 2 admin accounts
        for ($i = 1; $i <= 2; $i++) {
            User::firstOrCreate(
                ['email' => "admin_{$i}@military.edu.vn"],
                [
                    'name' => "Admin_{$i}",
                    'password' => Hash::make('123456'),
                    'role' => 'admin',
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
        $units = ['Tieu Doan 1', 'Tieu Doan 2', 'Tieu Doan 3', 'Dai Doi 1', 'Dai Doi 2'];
        
        // Create 5 manager accounts
        for ($i = 1; $i <= 5; $i++) {
            $manager = User::firstOrCreate(
                ['email' => "manager_{$i}@military.edu.vn"],
                [
                    'name' => "Manager_{$i}",
                    'password' => Hash::make('123456'),
                    'role' => 'manager',
                    'remember_token' => Str::random(10),
                ]
            );
            
            // Create manager detail with management unit
            ManagerDetail::firstOrCreate(
                ['user_id' => $manager->id],
                ['management_unit' => $units[($i - 1) % count($units)]]
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
                ['email' => "student_{$i}@student.edu.vn"],
                [
                    'name' => "Student_{$i}",
                    'password' => Hash::make('123456'),
                    'role' => 'student',
                    'remember_token' => Str::random(10),
                ]
            );
        }
    }
}
