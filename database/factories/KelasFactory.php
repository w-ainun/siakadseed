<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\TahunAkademik;
use App\Models\MataKuliah;
use App\Models\Dosen;
use Illuminate\Database\Eloquent\Factories\Factory;

class KelasFactory extends Factory
{
    protected $model = Kelas::class;

    public function definition()
    {
        $jamMulai = $this->faker->time('H:00:00');
        $sks = MataKuliah::find($this->faker->numberBetween(1,10))?->sks ?? $this->faker->randomElement([2,3,4]); // Example, better to get from mata_kuliah_id
        $durationHours = $sks > 3 ? 2 : 1.5; // Simplified logic
        $jamSelesai = date('H:i:s', strtotime($jamMulai . " +{$durationHours} hours"));


        $mataKuliah = MataKuliah::factory()->create();
        $dosen = Dosen::factory()->create(); // Can be refined to assign dosen from same prodi as mata kuliah

        return [
            'tahun_akademik_id' => TahunAkademik::factory(),
            'mata_kuliah_id' => $mataKuliah->kode_matakuliah,
            'dosen_id' => $dosen->id_dosen,
            'hari' => $this->faker->randomElement(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']),
            'jam_mulai' => $jamMulai,
            'jam_selesai' => $jamSelesai,
            'ruangan' => 'R-' . $this->faker->bothify('##??'),
            'kapasitas' => $this->faker->numberBetween(20, 50),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}