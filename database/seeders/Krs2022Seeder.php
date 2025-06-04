<?php
// database/seeders/Krs2022Seeder.php
namespace Database\Seeders;

class Krs2022Seeder extends BaseKrsSeeder
{
    protected function targetAngkatan(): int { return 2022; }
    protected function maxSemester(): int { return 6; } // 6 semester = 30.000 KRS
}


