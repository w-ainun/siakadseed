<?php

namespace Database\Factories;

use App\Models\NilaiAkhir;
use App\Models\KrsDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

class NilaiAkhirFactory extends Factory
{
    protected $model = NilaiAkhir::class;

    public function definition()
    {
        $nilaiAngka = $this->faker->randomFloat(2, 0, 100);
        $nilaiHuruf = 'E';
        if ($nilaiAngka >= 80) $nilaiHuruf = 'A';
        elseif ($nilaiAngka >= 70) $nilaiHuruf = 'B';
        elseif ($nilaiAngka >= 60) $nilaiHuruf = 'C';
        elseif ($nilaiAngka >= 50) $nilaiHuruf = 'D';

        return [
            'krs_detail_id' => KrsDetail::factory(),
            'nilai_angka' => $nilaiAngka,
            'nilai_huruf' => $nilaiHuruf,
        ];
    }
}