<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // Added for Schema facade

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting database seeding process...');

        // Temporarily disable foreign key checks for performance
        // Schema::disableForeignKeyConstraints(); // Preferred Laravel way

        // Fallback for direct DB commands if needed, Schema facade is better
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        $this->call([
            MasterDataSeeder::class,
            MahasiswaSeeder::class,
            KrsNilaiSeeder::class,
            // Add other seeders here if any
        ]);

        // Re-enable foreign key checks
        // Schema::enableForeignKeyConstraints(); // Preferred Laravel way

        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $this->command->info('Database seeding completed successfully!');
    }
}