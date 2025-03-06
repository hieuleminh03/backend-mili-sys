<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if admin credentials are set in environment
        $adminName = env('ADMIN_NAME');
        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');
        
        // Only seed if all required values are set and no admin exists
        if ($adminName && $adminEmail && $adminPassword 
            && User::where('role', 'admin')->count() === 0) {
            
            User::create([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
                'role' => 'admin'
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't want to delete the admin account when rolling back
        // as that could be destructive to production data
    }
}; 