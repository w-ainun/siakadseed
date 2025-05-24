<?php

namespace Database\Factories;

use App\Models\Dosen;
use App\Models\ProgramStudi;
use Illuminate\Database\Eloquent\Factories\Factory;

class DosenFactory extends Factory
{
    protected $model = Dosen::class;

    public function definition()
    {
        return [
            'nidn' => $this->faker->unique()->numerify('##########'),
            'nama_dosen' => $this->faker->name(),
            'gelar_depan' => $this->faker->optional(0.3)->title(),
            'gelar_belakang' => $this->faker->optional(0.7)->randomElement(['S.Kom', 'M.Kom', 'Ph.D', 'M.Sc.']),
            'tempat_lahir' => $this->faker->city(),
            'tanggal_lahir' => $this->faker->date(),
            'jenis_kelamin' => $this->faker->randomElement(['L', 'P']),
            'alamat' => $this->faker->address(),
            'no_telepon' => $this->faker->phoneNumber(),
            'status' => $this->faker->randomElement(['Aktif', 'Cuti', 'Keluar', 'Pensiun']),
            'prodi_id' => null, // Set to null by default or use ProgramStudi::factory() if appropriate.
                                // To associate with a specific Prodi, create or fetch Prodi first and pass its ID.
                                // e.g., $this->faker->boolean(70) ? ProgramStudi::factory() : null,
        ];
    }
}