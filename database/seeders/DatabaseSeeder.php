<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Memulai proses seeding database...');

        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        // Master data seeder (prodi, dosen, mata kuliah, kelas, tahun akademik, dll)
        $this->call([
            MasterDataSeeder::class,
            MataKuliahKelasSeeder::class,
            MahasiswaSeeder::class,
        ]);

        // Seeder data KRS dan nilai
        $this->call([
            Krs2019Seeder::class,
            Krs2020Seeder::class,
            Krs2021Seeder::class,
            Krs2022Seeder::class,
            Krs2023Seeder::class,
            Krs2024Seeder::class,
            KrsNilaiSeeder::class,
        ]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        $this->command->info('✅ Proses seeding database selesai!');
    }
}
