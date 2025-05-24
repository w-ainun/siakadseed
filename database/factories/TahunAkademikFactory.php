<?php

namespace Database\Factories;

use App\Models\TahunAkademik;
use Illuminate\Database\Eloquent\Factories\Factory;

class TahunAkademikFactory extends Factory
{
    protected $model = TahunAkademik::class;

    public function definition()
    {
        $year = $this->faker->numberBetween(2020, 2025);
        $startDate = $this->faker->dateTimeBetween($year . '-01-01', $year . '-06-01');
        $endDate = $this->faker->dateTimeBetween($startDate, $year . '-12-31');

        return [
            'tahun_akademik' => $year . '/' . ($year + 1),
            'semester' => $this->faker->randomElement(['Ganjil', 'Genap', 'Pendek']),
            'tanggal_mulai' => $startDate->format('Y-m-d'),
            'tanggal_selesai' => $endDate->format('Y-m-d'),
            'is_active' => $this->faker->boolean(25),
        ];
    }
}