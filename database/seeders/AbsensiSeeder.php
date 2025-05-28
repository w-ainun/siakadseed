<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;
use Illuminate\Support\Facades\DB;
use Faker\Factory as FakerFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AbsensiSeeder extends Seeder
{
    protected $faker;
    protected $batchSize = 2000; // Meningkatkan ukuran batch

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

        $mahasiswas = Mahasiswa::all()->keyBy('nim'); // KeyBy untuk akses cepat
        // Eager load mataKuliah dan kurikulum untuk filter prodi, dan keyBy
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

        $absensiToInsert = [];

        foreach ($kelasList as $kelas) {
            $progressBar->setMessage("Preparing Absensi data for Kelas: {$kelas->nama_kelas}");

            $tahunAkademik = $tahunAkademiks->get($kelas->tahun_akademik_id);
            if (!$tahunAkademik) {
                continue; // Lewati tanpa log
            }

            $numMeetings = $this->faker->numberBetween(14, 16); 

            foreach ($mahasiswas as $mahasiswa) {
                // Cek apakah mahasiswa ini seharusnya ada di kelas ini berdasarkan prodi
                if (($kelas->mataKuliah->kurikulum->prodi_id ?? null) !== $mahasiswa->prodi_id) {
                    continue;
                }

                for ($pertemuan = 1; $pertemuan <= $numMeetings; $pertemuan++) {
                    $statusAbsen = $this->faker->randomElement(['Hadir', 'Hadir', 'Hadir', 'Hadir', 'Izin', 'Sakit', 'Alpa']);
                    $absensiToInsert[] = [
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
                }
            }
            $progressBar->advance();
        }
        $progressBar->finish();

        $this->command->info("\nInserting Absensi data in batches...");
        $progressBar = $this->command->getOutput()->createProgressBar(count($absensiToInsert));
        $progressBar->start();

        // Insert semua data absensi dalam batch yang lebih besar
        foreach (array_chunk($absensiToInsert, $this->batchSize) as $chunk) { 
            try {
                Absensi::insert($chunk);
            } catch (\Illuminate\Database\QueryException $e) {
                // Log the error if it's not a duplicate key error
                if (!str_contains($e->getMessage(), 'unique_absensi_kelas_mahasiswa_pertemuan')) {
                    Log::error("Error inserting absensi batch: " . $e->getMessage());
                    throw $e;
                } else {
                    // Ini normal jika ada duplikat karena random generation
                    Log::warning("Duplicate absensi entry detected for batch. Skipping duplicates.");
                }
            }
            $progressBar->advance(count($chunk));
        }
        $progressBar->finish();

        $this->command->info("\nSeeding Absensi selesai.");
    }
}