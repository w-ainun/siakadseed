<?php

namespace Database\Factories;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use Illuminate\Database\Eloquent\Factories\Factory;

class AbsensiFactory extends Factory
{
    protected $model = Absensi::class;

    public function definition()
    {
        $status = $this->faker->randomElement(['Hadir', 'Izin', 'Sakit', 'Alpa']);
        $tanggal = $this->faker->dateTimeThisSemester()->format('Y-m-d');

        return [
            'kelas_id' => Kelas::factory(),
            'mahasiswa_id' => Mahasiswa::factory(),
            'status' => $status,
            'waktu_absen' => ($status === 'Hadir') ? $this->faker->dateTimeInInterval($tanggal . ' 07:00:00', '+2 hours') : null,
            'keterangan' => ($status === 'Izin' || $status === 'Sakit') ? $this->faker->sentence() : null,
            'pertemuan_ke' => $this->faker->numberBetween(1, 16),
            'tanggal' => $tanggal,
            'materi' => $this->faker->sentence(4),
            'is_terlaksana' => $this->faker->boolean(95),
        ];
    }
}