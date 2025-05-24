<?php

namespace Database\Factories;

use App\Models\MataKuliah;
use App\Models\Kurikulum;
use Illuminate\Database\Eloquent\Factories\Factory;

class MataKuliahFactory extends Factory
{
    protected $model = MataKuliah::class;

    public function definition()
    {
        return [
            // 'kode_matakuliah' is auto-incrementing
            'nama_mk' => $this->faker->catchPhrase(),
            'sks' => $this->faker->randomElement([1, 2, 3, 4, 6]),
            'semester' => $this->faker->numberBetween(1, 8),
            'jenis' => $this->faker->randomElement(['Wajib', 'Pilihan']),
            'kurikulum_id' => Kurikulum::factory(),
        ];
    }
}