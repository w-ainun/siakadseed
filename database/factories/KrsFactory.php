<?php

namespace Database\Factories;

use App\Models\Krs;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon; // Import Carbon jika ingin kontrol lebih atau untuk perbandingan

class KrsFactory extends Factory
{
    protected $model = Krs::class;

    public function definition()
    {
        $status = $this->faker->randomElement(['Draft', 'Diajukan', 'Disetujui', 'Ditolak']);

        // PERBAIKAN: Ganti dateTimeThisSemester dengan metode Faker yang valid
        // Misalnya, dateTimeThisYear() untuk tanggal dalam tahun ini,
        // atau dateTimeBetween('-1 year', 'now') untuk rentang yang lebih luas.
        // Atau jika ingin tanggal pengajuan selalu sebelum tanggal hari ini:
        $pengajuan = $this->faker->dateTimeBetween('-1 year', 'now'); // Contoh: antara 1 tahun lalu dan sekarang

        $persetujuan = null;
        // Pastikan tanggal persetujuan setelah tanggal pengajuan jika statusnya relevan
        if (in_array($status, ['Disetujui', 'Ditolak'])) {
            // Menghasilkan tanggal persetujuan antara tanggal pengajuan dan sekarang
            // Tambahkan minimal beberapa jam/hari setelah pengajuan agar logis
            $minPersetujuan = Carbon::instance($pengajuan)->addHours(1);
            $maxPersetujuan = Carbon::now();

            // Jika minPersetujuan sudah melewati maxPersetujuan (misal pengajuan baru saja dibuat),
            // maka set persetujuan sama dengan maxPersetujuan atau sedikit setelah pengajuan.
            if ($minPersetujuan->greaterThanOrEqualTo($maxPersetujuan)) {
                $persetujuan = $minPersetujuan->addMinutes($this->faker->numberBetween(5, 60))->toDateTimeString();
            } else {
                $persetujuan = $this->faker->dateTimeBetween($minPersetujuan, $maxPersetujuan);
            }
        }

        return [
            // Atribut ini akan di-override oleh KrsNilaiSeeder jika factory dipanggil dari sana
            'mahasiswa_id' => Mahasiswa::factory(),
            'tahun_akademik_id' => TahunAkademik::factory(),

            'tanggal_pengajuan' => $pengajuan,
            'tanggal_persetujuan' => $persetujuan,
            'status' => $status,
            'catatan' => $this->faker->optional(0.3)->sentence(),
            'total_sks' => 0, // Diisi oleh seeder berdasarkan KrsDetail
        ];
    }
}