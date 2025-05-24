<?php

namespace Database\Factories;

use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;
use Illuminate\Database\Eloquent\Factories\Factory;

class KrsFactory extends Factory
{
    protected $model = Krs::class;

    public function definition()
    {
        $status = $this->faker->randomElement(['Draft', 'Diajukan', 'Disetujui', 'Ditolak']);
        $pengajuan = $this->faker->dateTimeThisSemester();
        $persetujuan = null;
        if (in_array($status, ['Disetujui', 'Ditolak'])) {
            $persetujuan = $this->faker->dateTimeBetween($pengajuan, 'now');
        }

        return [
            'mahasiswa_id' => Mahasiswa::factory(),
            'tahun_akademik_id' => TahunAkademik::factory(),
            'tanggal_pengajuan' => $pengajuan,
            'tanggal_persetujuan' => $persetujuan,
            'status' => $status,
            'catatan' => $this->faker->optional(0.3)->sentence(),
            'total_sks' => 0, // Typically calculated based on krs_detail
        ];
    }
}