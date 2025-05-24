<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\Dosen;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\DB; // Uncomment jika ingin menggunakan DB::insert() untuk performa maksimal

class MahasiswaSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Memulai proses seeding data Mahasiswa...');

        // 1. Ambil data Program Studi
        $programStudis = ProgramStudi::select('id_prodi', 'fakultas_id')->get()->keyBy('id_prodi');

        if ($programStudis->isEmpty()) {
            $warningMessage = 'Tidak ada data Program Studi ditemukan. MahasiswaSeeder tidak dapat berjalan.';
            Log::warning($warningMessage);
            $this->command->warn($warningMessage);
            return;
        }
        $prodiIdsArray = $programStudis->pluck('id_prodi')->toArray();

        // 2. Ambil dan kelompokkan data Dosen untuk Pembimbing Akademik (OPTIMASI)
        $this->command->info('Mengumpulkan data Dosen untuk Pembimbing Akademik...');
        $dosenByProdi = Dosen::select('id_dosen', 'prodi_id')->where('status', 'Aktif')->get()
            ->groupBy('prodi_id')
            ->map(fn($group) => $group->pluck('id_dosen')->toArray());
        
        // Ambil semua dosen aktif sebagai fallback jika prodi tidak punya dosen atau dosen_by_prodi kosong
        $allActiveDosenIds = Dosen::where('status', 'Aktif')->pluck('id_dosen')->toArray();

        if (empty($allActiveDosenIds)) {
            $this->command->warn('Tidak ada data Dosen (aktif) ditemukan. Mahasiswa akan di-seed tanpa Dosen PA atau dengan Dosen PA null.');
            // Seeding tetap berjalan, dosen_pa_id bisa jadi null
        }

        $tahunMasukRange = range(2019, 2024); // Minimal 6 tahun
        $mahasiswaPerTahun = 5000;           // Minimal 5000 mahasiswa per tahun
        $totalMahasiswaCreated = 0;
        $nimCounters = []; // Counter NIM untuk run ini, tidak persistent antar run seeder

        // Inisialisasi progress bar
        $totalToSeed = count($tahunMasukRange) * $mahasiswaPerTahun;
        $progressBar = $this->command->getOutput()->createProgressBar($totalToSeed);
        $progressBar->setFormatDefinition('custom', ' %current%/%max% [%bar%] %percent:3s%% -- %message% (Est: %estimated:-6s% Left: %remaining:-6s%)');
        $progressBar->setFormat('custom');
        $progressBar->setMessage('Memulai seeding mahasiswa...');
        $progressBar->start();

        foreach ($tahunMasukRange as $tahun) {
            $progressBar->setMessage("Seeding tahun {$tahun}");
            $mahasiswaDataBatch = [];

            for ($i = 0; $i < $mahasiswaPerTahun; $i++) {
                if (empty($prodiIdsArray)) {
                    Log::warning('Array ID Program Studi kosong. Loop dihentikan.');
                    $this->command->error('Array ID Program Studi kosong. Proses seeding mungkin tidak lengkap.');
                    break; 
                }
                $prodiId = $prodiIdsArray[array_rand($prodiIdsArray)];
                $currentProdi = $programStudis->get($prodiId);

                if (!$currentProdi || !isset($currentProdi->fakultas_id)) {
                    Log::warning("Mahasiswa dilewati: Program Studi dengan ID {$prodiId} tidak ditemukan atau tidak memiliki fakultas_id.");
                    $progressBar->advance(); // Tetap advance progress bar
                    continue;
                }
                $fakultasId = $currentProdi->fakultas_id;

                // Pilih Dosen PA (OPTIMASI)
                $dosenPaId = null;
                if ($dosenByProdi->has($prodiId) && !empty($dosenByProdi->get($prodiId))) {
                    $possibleDosenPaIds = $dosenByProdi->get($prodiId);
                    $dosenPaId = $possibleDosenPaIds[array_rand($possibleDosenPaIds)];
                } elseif (!empty($allActiveDosenIds)) { // Fallback ke dosen aktif mana saja
                    $dosenPaId = $allActiveDosenIds[array_rand($allActiveDosenIds)];
                }

                // Generate NIM
                $nimPrefixKey = $tahun . '-' . $fakultasId . '-' . $prodiId;
                $nimCounters[$nimPrefixKey] = ($nimCounters[$nimPrefixKey] ?? 0) + 1;
                $nomorUrut = $nimCounters[$nimPrefixKey];

                // Format NIM: tahun(4) fakultas_id(2) prodi_id(3) nomor_urut(4)
                // PASTIKAN panjang digit fakultas_id dan prodi_id sesuai!
                // Jika fakultas_id bisa > 99, ubah %02d. Jika prodi_id bisa > 999, ubah %03d.
                if ($fakultasId > 99) {
                    Log::warning("Format NIM mungkin salah: fakultas_id ($fakultasId) lebih dari 2 digit untuk NIM.");
                }
                if ($prodiId > 999) {
                     Log::warning("Format NIM mungkin salah: prodi_id ($prodiId) lebih dari 3 digit untuk NIM.");
                }
                $nim = sprintf("%04d%02d%03d%04d", $tahun, $fakultasId, $prodiId, $nomorUrut);

                $mahasiswaDataBatch[] = [
                    'nim' => $nim,
                    'tahun_masuk' => $tahun,
                    'prodi_id' => $prodiId,
                    'dosen_pa_id' => $dosenPaId, // Bisa null jika tidak ada dosen
                    'status' => 'Aktif',
                    // Factory akan mengisi: nama_mahasiswa, tempat_lahir, dll.
                    // serta created_at, updated_at jika tidak ada di sini
                ];

                // Batch insert, ukuran batch bisa disesuaikan (misal 500-1000)
                if (count($mahasiswaDataBatch) >= 1000) {
                    Mahasiswa::factory()->createMany($mahasiswaDataBatch);
                    $totalMahasiswaCreated += count($mahasiswaDataBatch);
                    $mahasiswaDataBatch = []; // Reset batch
                    $progressBar->advance(1000);
                }
            }

            // Insert sisa data dalam batch terakhir
            if (!empty($mahasiswaDataBatch)) {
                $batchCount = count($mahasiswaDataBatch);
                Mahasiswa::factory()->createMany($mahasiswaDataBatch);
                $totalMahasiswaCreated += $batchCount;
                $progressBar->advance($batchCount);
            }
        }

        $progressBar->finish();
        $this->command->info("\nTotal Mahasiswa yang berhasil di-seed: " . $totalMahasiswaCreated);
    }
}