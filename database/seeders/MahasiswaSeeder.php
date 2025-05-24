<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi; // Pastikan model ini ada dan memiliki kolom fakultas_id
use App\Models\Dosen;       // Pastikan model ini ada
use Illuminate\Support\Facades\Log;

class MahasiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Memulai proses seeding data Mahasiswa...');

        // Ambil data Program Studi beserta fakultas_id nya.
        // Diasumsikan ProgramStudi memiliki kolom 'id_prodi' sebagai primary key dan 'fakultas_id'.
        // 'keyBy('id_prodi')' memudahkan pencarian prodi berdasarkan ID.
        $programStudis = ProgramStudi::select('id_prodi', 'fakultas_id')->get()->keyBy('id_prodi');

        if ($programStudis->isEmpty()) {
            $warningMessage = 'Tidak ada data Program Studi ditemukan. MahasiswaSeeder tidak dapat berjalan.';
            Log::warning($warningMessage);
            $this->command->warn($warningMessage);
            return;
        }
        // Ambil array dari ID prodi saja untuk pemilihan acak
        $prodiIdsArray = $programStudis->pluck('id_prodi')->toArray();


        $dosenIds = Dosen::pluck('id_dosen')->toArray(); // Ambil semua ID Dosen yang ada

        $tahunMasukRange = range(2019, 2024); // Rentang tahun masuk, misal 6 tahun terakhir
        $mahasiswaPerTahun = 5000; // Target jumlah mahasiswa per tahun masuk
        $totalMahasiswaCreated = 0;

        // Array untuk menyimpan nomor urut NIM terakhir untuk setiap kombinasi
        // tahun_masuk + fakultas_id + prodi_id
        // Counter ini akan reset setiap kali seeder dijalankan.
        // Untuk persistensi antar seeder run, perlu logika query ke DB.
        $nimCounters = [];

        foreach ($tahunMasukRange as $tahun) {
            $this->command->info("Seeding Mahasiswa untuk tahun masuk: {$tahun} (Target: {$mahasiswaPerTahun})");
            $mahasiswaDataBatch = [];

            for ($i = 0; $i < $mahasiswaPerTahun; $i++) {
                // Pilih prodi secara acak
                if (empty($prodiIdsArray)) {
                    $warningMessage = 'Array ID Program Studi kosong. Tidak bisa memilih prodi.';
                    Log::warning($warningMessage);
                    $this->command->warn($warningMessage);
                    break; // Keluar dari loop for jika tidak ada prodi
                }
                $prodiId = $prodiIdsArray[array_rand($prodiIdsArray)];
                $currentProdi = $programStudis->get($prodiId); 

                if (!$currentProdi || !isset($currentProdi->fakultas_id)) {
                    Log::warning("Mahasiswa dilewati: Program Studi dengan ID {$prodiId} tidak ditemukan atau tidak memiliki fakultas_id.");
                    continue; 
                }
                $fakultasId = $currentProdi->fakultas_id;

                // Dapatkan dosen PA (Pembimbing Akademik) secara acak
                $dosenPaId = null;
                if (!empty($dosenIds)) {
                    // Prioritaskan dosen dari prodi yang sama
                    $dosenPaProdi = Dosen::where('prodi_id', $prodiId)->inRandomOrder()->first();
                    if ($dosenPaProdi) {
                        $dosenPaId = $dosenPaProdi->id_dosen;
                    } else {
                        // Jika tidak ada dosen di prodi tsb, pilih dosen acak lain
                        $dosenPaRandom = Dosen::inRandomOrder()->first();
                        $dosenPaId = $dosenPaRandom ? $dosenPaRandom->id_dosen : null;
                    }
                }

                // --- GENERATE NIM ---
                // Kunci unik untuk counter berdasarkan tahun, fakultas, dan prodi
                $nimPrefixKey = $tahun . '-' . $fakultasId . '-' . $prodiId;

                if (!isset($nimCounters[$nimPrefixKey])) {
                    $nimCounters[$nimPrefixKey] = 0; 
                }
                $nimCounters[$nimPrefixKey]++;
                $nomorUrut = $nimCounters[$nimPrefixKey];

                // Format NIM: tahun_masuk (4 digit), fakultas_id (misal 2 digit), prodi_id (misal 3 digit), nomor_urut (misal 4 digit)
                // Sesuaikan format sprintf (%0Xd) sesuai kebutuhan panjang digit ID Anda.
                // Contoh: 2024 (tahun) 01 (fakultas) 005 (prodi) 0001 (no urut) -> 2024010050001
                $nim = sprintf("%04d%02d%03d%04d", $tahun, $fakultasId, $prodiId, $nomorUrut);
                // --- END GENERATE NIM ---

                $mahasiswaDataBatch[] = [
                    'nim' => $nim, 
                    'tahun_masuk' => $tahun,
                    'prodi_id' => $prodiId,
                    'dosen_pa_id' => $dosenPaId,
                    'status' => 'Aktif', // Status default untuk mahasiswa baru
                    // Atribut lain seperti 'nama_mahasiswa', 'tempat_lahir', 'email_pribadi', dll.
                    // akan diisi oleh MahasiswaFactory
                ];

                // Proses batch insert untuk optimasi performa
                if (count($mahasiswaDataBatch) >= 500) { // Insert per 500 data
                    Mahasiswa::factory()->createMany($mahasiswaDataBatch);
                    $totalMahasiswaCreated += count($mahasiswaDataBatch);
                    $mahasiswaDataBatch = []; // Reset batch
                    $this->command->getOutput()->write('.'); // Indikator progress
                }
            }

            // Insert sisa data dalam batch terakhir jika ada
            if (!empty($mahasiswaDataBatch)) {
                Mahasiswa::factory()->createMany($mahasiswaDataBatch);
                $totalMahasiswaCreated += count($mahasiswaDataBatch);
                $this->command->getOutput()->write('.');
            }
            $this->command->line("\nSelesai seeding Mahasiswa untuk tahun {$tahun}. Total sejauh ini: {$totalMahasiswaCreated}");
        }
        $this->command->info("Total Mahasiswa yang berhasil di-seed: " . $totalMahasiswaCreated);
    }
}