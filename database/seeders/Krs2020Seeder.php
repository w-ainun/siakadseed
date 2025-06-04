<?php
// database/seeders/Krs2020Seeder.php
namespace Database\Seeders;

class Krs2020Seeder extends BaseKrsSeeder
{
    protected function targetAngkatan(): int { return 2020; }
    protected function maxSemester(): int { return 8; }
}

