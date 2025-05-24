<?php

namespace Database\Factories;

use App\Models\Ips;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;
use Illuminate\Database\Eloquent\Factories\Factory;

class IpsFactory extends Factory
{
    protected $model = Ips::class;

    public function definition()
    {
        return [
            'mahasiswa_id' => Mahasiswa::factory(),
            'tahun_akademik_id' => TahunAkademik::factory(),
            'ips' => $this->faker->randomFloat(2, 1.00, 4.00),
            'total_sks' => $this->faker->numberBetween(12, 24),
        ];
    }
}