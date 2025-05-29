<?php
// database/seeders/KrsSeeder.php
use Illuminate\Database\Seeder;

class KrsSeeder extends Seeder
{
    public function run()
    {
        // Panggil seeder berdasarkan angkatan
        $this->call([
            Krs2019Seeder::class,
            Krs2020Seeder::class,
            Krs2021Seeder::class,
            Krs2022Seeder::class,
            Krs2023Seeder::class,
            Krs2024Seeder::class,
            Krs2025Seeder::class,
        ]);
    }
}
