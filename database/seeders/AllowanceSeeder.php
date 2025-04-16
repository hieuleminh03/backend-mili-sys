<?php

namespace Database\Seeders;

use App\Models\MonthlyAllowance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AllowanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createMonthlyAllowances();
    }
    
    /**
     * Create monthly allowances for students
     */
    private function createMonthlyAllowances(): void
    {
        // Clear allowances created today
        MonthlyAllowance::whereDate('created_at', Carbon::today())->delete();
        
        // Get all students
        $students = User::where('role', User::ROLE_STUDENT)->get();
        
        if ($students->isEmpty()) {
            return;
        }
        
        $currentYear = date('Y');
        $currentMonth = date('n');
        
        // Create allowances for the past 4 months and current month
        for ($monthOffset = 4; $monthOffset >= 0; $monthOffset--) {
            // Calculate month and year
            $month = $currentMonth - $monthOffset;
            $year = $currentYear;
            
            // Handle previous year months
            while ($month <= 0) {
                $month += 12;
                $year--;
            }
            
            foreach ($students as $student) {
                // Amount ranges from 500,000 to 2,000,000 VND
                $amount = rand(5, 20) * 100000;
                
                // Past months are usually received, current month may be pending
                $received = ($monthOffset > 0) ? (rand(1, 10) <= 9) : (rand(1, 10) <= 3); // 90% for past, 30% for current
                $receivedAt = $received ? Carbon::create($year, $month, rand(10, 28)) : null;
                
                // Notes based on status
                $notes = $received ? 'Da nhan du phu cap' : 'Chua nhan phu cap';
                
                MonthlyAllowance::firstOrCreate(
                    [
                        'user_id' => $student->id,
                        'month' => $month,
                        'year' => $year
                    ],
                    [
                        'amount' => $amount,
                        'received' => $received,
                        'received_at' => $receivedAt,
                        'notes' => $notes
                    ]
                );
            }
        }
    }
}
