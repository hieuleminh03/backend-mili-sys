<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log; // Add Log facade

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin credentials are set in environment
        $adminName = env('ADMIN_NAME');
        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');

        // Check if an admin user already exists
        $adminExists = User::where('role', 'admin')->exists();

        if ($adminName && $adminEmail && $adminPassword) {
            if (!$adminExists) {
                User::create([
                    'name' => $adminName,
                    'email' => $adminEmail,
                    'password' => Hash::make($adminPassword),
                    'role' => 'admin',
                ]);
                $this->command->info('Admin user created successfully.');
            } else {
                $this->command->info('Admin user already exists. Skipping creation.');
            }
        } else {
            // Log a warning if credentials are not set
            Log::warning('Admin credentials (ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD) not fully set in .env file. Skipping admin user creation.');
            $this->command->warn('Admin credentials not fully set in .env. Skipping admin user creation.');
        }
    }
}