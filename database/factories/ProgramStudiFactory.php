<?php

namespace Database\Factories;

use App\Models\ProgramStudi;
use App\Models\Fakultas;
use App\Models\Dosen;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramStudiFactory extends Factory
{
    protected $model = ProgramStudi::class;

    public function definition()
    {
        return [
            'nama_prodi' => 'Program Studi ' . $this->faker->jobTitle(),
            'jenjang' => $this->faker->randomElement(['D3', 'S1', 'S2', 'S3']),
            'fakultas_id' => Fakultas::factory(),
            'kaprodi_id' => null, // Set to null to avoid circular dependency issues with Dosen factory.
                                  // Can be populated later or via a specific state.
                                  // Or: $this->faker->boolean(70) ? Dosen::factory()->state(['prodi_id' => null]) : null,
        ];
    }
}