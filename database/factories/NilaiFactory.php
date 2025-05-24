<?php

namespace Database\Factories;

use App\Models\Nilai;
use App\Models\KrsDetail;
use App\Models\KomponenNilai;
use Illuminate\Database\Eloquent\Factories\Factory;

class NilaiFactory extends Factory
{
    protected $model = Nilai::class;

    public function definition()
    {
        // For consistency, KomponenNilai should belong to the same Kelas as the KrsDetail's Kelas.
        // This might require more complex factory logic or states.
        $krsDetail = KrsDetail::factory()->create();
        $komponenNilai = KomponenNilai::factory()->create(['kelas_id' => $krsDetail->kelas->id_kelas]);


        return [
            'krs_detail_id' => $krsDetail->id_krsdetail,
            'komponen_nilai_id' => $komponenNilai->id_komponennilai,
            'nilai_angka' => $this->faker->randomFloat(2, 40, 100),
        ];
    }
}