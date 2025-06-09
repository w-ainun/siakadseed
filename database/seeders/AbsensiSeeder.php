<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;
use Illuminate\Support\Facades\DB;
use Faker\Factory as FakerFactory;
use Illuminate\Support\Facades\Log;

class AbsensiSeeder extends Seeder
{
    protected $faker;
    protected $batchSize = 2000;

    public function __construct()
    {
        $this->faker = FakerFactory::create('id_ID');
    }

    public function run()
    {
        $this->command->info('Memulai seeding data Absensi...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('absensi')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $mahasiswas = Mahasiswa::all()->keyBy('nim');
        $kelasList = Kelas::with('mataKuliah.kurikulum')->get()->keyBy('id_kelas');
        $tahunAkademiks = TahunAkademik::all()->keyBy('id_tahunakademik');

        if ($mahasiswas->isEmpty() || $kelasList->isEmpty() || $tahunAkademiks->isEmpty()) {
            $this->command->error('Data dasar (Mahasiswa, Kelas, Tahun Akademik) tidak ditemukan. Seeder dihentikan.');
            return;
        }

        $totalKelas = $kelasList->count();
        $progressBar = $this->command->getOutput()->createProgressBar($totalKelas);
        $progressBar->setFormatDefinition('custom', ' %current%/%max% [%bar%] %percent:3s%% -- %message% (Est: %estimated:-6s% Left: %remaining:-6s%)');
        $progressBar->setFormat('custom');
        $progressBar->start();

        foreach ($kelasList as $kelas) {
            $progressBar->setMessage("Preparing Absensi for Kelas: {$kelas->nama_kelas}");

            $tahunAkademik = $tahunAkademiks->get($kelas->tahun_akademik_id);
            if (!$tahunAkademik) {
                continue;
            }

            $numMeetings = $this->faker->numberBetween(14, 16);
            $batchAbsensi = [];

            foreach ($mahasiswas as $mahasiswa) {
                if (($kelas->mataKuliah->kurikulum->prodi_id ?? null) !== $mahasiswa->prodi_id) {
                    continue;
                }

                for ($pertemuan = 1; $pertemuan <= $numMeetings; $pertemuan++) {
                    $statusAbsen = $this->faker->randomElement(['Hadir', 'Hadir', 'Hadir', 'Hadir', 'Izin', 'Sakit', 'Alpa']);
                    $batchAbsensi[] = [
                        'kelas_id' => $kelas->id_kelas,
                        'mahasiswa_id' => $mahasiswa->nim,
                        'status' => $statusAbsen,
                        'waktu_absen' => $this->faker->dateTimeBetween($tahunAkademik->tanggal_mulai, $tahunAkademik->tanggal_selesai)->format('Y-m-d H:i:s'),
                        'keterangan' => ($statusAbsen != 'Hadir') ? $this->faker->sentence(3) : null,
                        'pertemuan_ke' => $pertemuan,
                        'tanggal' => $this->faker->dateTimeBetween($tahunAkademik->tanggal_mulai, $tahunAkademik->tanggal_selesai)->format('Y-m-d'),
                        'materi' => $this->faker->sentence(4),
                        'is_terlaksana' => true,
                        'created_at' => now()->format('Y-m-d H:i:s'),
                        'updated_at' => now()->format('Y-m-d H:i:s'),
                    ];

                    if (count($batchAbsensi) >= $this->batchSize) {
                        $this->insertBatch($batchAbsensi);
                        $batchAbsensi = [];
                    }
                }
            }

            if (!empty($batchAbsensi)) {
                $this->insertBatch($batchAbsensi);
            }

            $progressBar->advance();
            Log::info("Selesai proses untuk Kelas ID: {$kelas->id_kelas} ({$kelas->nama_kelas})");
        }

        $progressBar->finish();
        $this->command->info("\nSeeding Absensi selesai.");
    }

    protected function insertBatch(array $data)
    {
        try {
            Absensi::insert($data);
        } catch (\Illuminate\Database\QueryException $e) {
            if (!str_contains($e->getMessage(), 'unique_absensi_kelas_mahasiswa_pertemuan')) {
                Log::error("Gagal insert batch absensi: " . $e->getMessage());
                throw $e;
            } else {
                Log::warning("Terdeteksi duplikat absensi. Batch dilewati.");
            }
        }
    }
}
