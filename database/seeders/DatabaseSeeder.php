<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            TermSeeder::class,
            ClassSeeder::class,
            CourseSeeder::class,
            FitnessSeeder::class,
            ViolationSeeder::class,
            EquipmentSeeder::class,
            AllowanceSeeder::class,
        ]);
    }
}
