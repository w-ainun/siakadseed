<?php

namespace Database\Factories;

use App\Models\KomponenNilai;
use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

class KomponenNilaiFactory extends Factory
{
    protected $model = KomponenNilai::class;

    public function definition()
    {
        return [
            'kelas_id' => Kelas::factory(),
            'nama_komponen' => $this->faker->randomElement(['Ujian Tengah Semester', 'Ujian Akhir Semester', 'Tugas', 'Kuis', 'Kehadiran', 'Partisipasi']),
            'bobot' => $this->faker->randomFloat(2, 10, 40), // Example: 10.00 to 40.00
        ];
    }
}