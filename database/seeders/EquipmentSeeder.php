<?php

namespace Database\Seeders;

use App\Models\MilitaryEquipmentType;
use App\Models\StudentEquipmentReceipt;
use App\Models\User;
use App\Models\YearlyEquipmentDistribution;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create equipment types
        $equipmentTypes = $this->createEquipmentTypes();
        
        // Create yearly distributions
        $distributions = $this->createYearlyDistributions($equipmentTypes);
        
        // Create student equipment receipts
        $this->createStudentReceipts($distributions);
    }
    
    /**
     * Create military equipment types
     * 
     * @return array
     */
    private function createEquipmentTypes(): array
    {
        $equipmentData = [
            ['name' => 'Quan phuc', 'description' => 'Quan phuc thuong ngay'],
            ['name' => 'Mu bao hiem', 'description' => 'Mu bao hiem quan doi tieu chuan'],
            ['name' => 'Giay combat', 'description' => 'Giay chien dau quan su'],
            ['name' => 'Ba lo quan doi', 'description' => 'Ba lo dung dung cu quan su'],
            ['name' => 'Ao mua', 'description' => 'Ao mua quan doi'],
            ['name' => 'Den pin', 'description' => 'Den pin chien thuat'],
            ['name' => 'Binh nuoc', 'description' => 'Binh dung nuoc tieu chuan quan doi'],
            ['name' => 'Dia bay thuc hanh', 'description' => 'Dia bay dung de tap luyen'],
            ['name' => 'Bo dung cu ve sinh', 'description' => 'Bo dung cu ve sinh ca nhan']
        ];
        
        $types = [];
        
        foreach ($equipmentData as $data) {
            $types[] = MilitaryEquipmentType::firstOrCreate(
                ['name' => $data['name']],
                ['description' => $data['description']]
            );
        }
        
        return $types;
    }
    
    /**
     * Create yearly equipment distributions
     * 
     * @param array $equipmentTypes
     * @return array
     */
    private function createYearlyDistributions(array $equipmentTypes): array
    {
        // Clear existing distributions created today
        YearlyEquipmentDistribution::whereDate('created_at', Carbon::today())->delete();
        
        $currentYear = date('Y');
        $distributions = [];
        
        foreach ($equipmentTypes as $type) {
            // Create current year distribution
            $distributions[] = YearlyEquipmentDistribution::firstOrCreate(
                [
                    'equipment_type_id' => $type->id,
                    'year' => $currentYear
                ],
                ['quantity' => rand(30, 100)]
            );
            
            // Also create next year's distribution for some types (planning)
            if (rand(1, 10) <= 7) { // 70% chance for next year planning
                $distributions[] = YearlyEquipmentDistribution::firstOrCreate(
                    [
                        'equipment_type_id' => $type->id,
                        'year' => $currentYear + 1
                    ],
                    ['quantity' => rand(30, 100)]
                );
            }
        }
        
        return $distributions;
    }
    
    /**
     * Create student equipment receipts
     * 
     * @param array $distributions
     */
    private function createStudentReceipts(array $distributions): void
    {
        // Clear existing receipts created today
        StudentEquipmentReceipt::whereDate('created_at', Carbon::today())->delete();
        
        // Get all students
        $students = User::where('role', 'student')->get();
        
        if ($students->isEmpty() || empty($distributions)) {
            return;
        }
        
        // Filter out current year distributions
        $currentYear = date('Y');
        $currentYearDistributions = array_filter($distributions, function($dist) use ($currentYear) {
            return $dist->year == $currentYear;
        });
        
        if (empty($currentYearDistributions)) {
            return;
        }
        
        foreach ($students as $student) {
            // Each student gets 3-7 different equipment items
            $itemCount = rand(3, 7);
            $selectedDistributions = array_rand(array_flip(array_column($currentYearDistributions, 'id')), 
                                              min($itemCount, count($currentYearDistributions)));
            
            if (!is_array($selectedDistributions)) {
                $selectedDistributions = [$selectedDistributions];
            }
            
            foreach ($selectedDistributions as $distId) {
                // Find the distribution
                $distribution = null;
                foreach ($currentYearDistributions as $dist) {
                    if ($dist->id == $distId) {
                        $distribution = $dist;
                        break;
                    }
                }
                
                if (!$distribution) continue;
                
                // 70% chance the student has received the equipment
                $received = rand(1, 10) <= 7;
                $receivedAt = $received ? Carbon::now()->subDays(rand(1, 90)) : null;
                $notes = $received ? 
                    'Da nhan du trang bi' : 
                    'Chua nhan trang bi';
                
                StudentEquipmentReceipt::create([
                    'user_id' => $student->id,
                    'distribution_id' => $distribution->id,
                    'received' => $received,
                    'received_at' => $receivedAt,
                    'notes' => $notes
                ]);
            }
        }
    }
}
