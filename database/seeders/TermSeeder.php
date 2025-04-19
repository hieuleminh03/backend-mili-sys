<?php
namespace Database\Seeders;

use App\Models\Term;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createTerms();
    }

    /**
     * Create academic terms - past, current, and future
     */
    private function createTerms(): void
    {
        $currentYear  = date('Y');
        $previousYear = $currentYear - 1;
        $nextYear     = $currentYear + 1;

        // Previous year's second term
        Term::firstOrCreate(
            ['name' => "{$previousYear}B"],
            [
                'start_date'       => Carbon::create($previousYear, 8, 1),
                'end_date'         => Carbon::create($previousYear, 12, 31),
                'roster_deadline'  => Carbon::create($previousYear, 9, 1),
                'grade_entry_date' => Carbon::create($previousYear, 12, 15),
            ]
        );

        // Current year's first term (current term)
        Term::firstOrCreate(
            ['name' => "{$currentYear}A"],
            [
                'start_date'       => Carbon::create($currentYear, 2, 1),
                'end_date'         => Carbon::create($currentYear, 6, 30),
                'roster_deadline'  => Carbon::create($currentYear, 3, 1),
                'grade_entry_date' => Carbon::create($currentYear, 7, 15),
            ]
        );

        // Current year's second term (upcoming term)
        Term::firstOrCreate(
            ['name' => "{$currentYear}B"],
            [
                'start_date'       => Carbon::create($currentYear, 8, 1),
                'end_date'         => Carbon::create($currentYear, 12, 31),
                'roster_deadline'  => Carbon::create($currentYear, 9, 1),
                'grade_entry_date' => Carbon::create($currentYear, 12, 15),
            ]
        );

        // Next year's first term (future planning)
        Term::firstOrCreate(
            ['name' => "{$nextYear}A"],
            [
                'start_date'       => Carbon::create($nextYear, 2, 1),
                'end_date'         => Carbon::create($nextYear, 6, 30),
                'roster_deadline'  => Carbon::create($nextYear, 3, 1),
                'grade_entry_date' => Carbon::create($nextYear, 7, 15),
            ]
        );
    }
}
