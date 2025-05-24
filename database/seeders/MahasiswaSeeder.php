<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mahasiswa; // Pastikan ini di-import
use App\Models\ProgramStudi;
use App\Models\Dosen;
use Illuminate\Support\Facades\Log;

class MahasiswaSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Memulai proses seeding data Mahasiswa...');

        $programStudis = ProgramStudi::select('id_prodi', 'fakultas_id')->get()->keyBy('id_prodi');

        if ($programStudis->isEmpty()) {
            $warningMessage = 'Tidak ada data Program Studi ditemukan. MahasiswaSeeder tidak dapat berjalan.';
            Log::warning($warningMessage);
            $this->command->warn($warningMessage);
            return;
        }
        $prodiIdsArray = $programStudis->pluck('id_prodi')->toArray();

        $this->command->info('Mengumpulkan data Dosen untuk Pembimbing Akademik...');
        $dosenByProdi = Dosen::select('id_dosen', 'prodi_id')->where('status', 'Aktif')->get()
            ->groupBy('prodi_id')
            ->map(fn($group) => $group->pluck('id_dosen')->toArray());
        
        $allActiveDosenIds = Dosen::where('status', 'Aktif')->pluck('id_dosen')->toArray();

        if (empty($allActiveDosenIds)) {
            $this->command->warn('Tidak ada data Dosen (aktif) ditemukan. Mahasiswa akan di-seed tanpa Dosen PA atau dengan Dosen PA null.');
        }

        $tahunMasukRange = range(2019, 2024); // Kembalikan ke rentang tahun normal
        $mahasiswaPerTahun = 5000;           // Kembalikan ke jumlah normal
        $totalMahasiswaCreated = 0;
        $nimCounters = [];

        $totalToSeed = count($tahunMasukRange) * $mahasiswaPerTahun;
        $progressBar = $this->command->getOutput()->createProgressBar($totalToSeed);
        $progressBar->setFormatDefinition('custom', ' %current%/%max% [%bar%] %percent:3s%% -- %message% (Est: %estimated:-6s% Left: %remaining:-6s%)');
        $progressBar->setFormat('custom');
        $progressBar->setMessage('Memulai seeding mahasiswa...');
        $progressBar->start();

        foreach ($tahunMasukRange as $tahun) {
            $progressBar->setMessage("Seeding tahun {$tahun}");
            $mahasiswaFullDataBatch = []; // Batch untuk data yang sudah digabung

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
                    $progressBar->advance();
                    continue;
                }
                $fakultasId = $currentProdi->fakultas_id;
                $dosenPaId = null;
                if ($dosenByProdi->has($prodiId) && !empty($dosenByProdi->get($prodiId))) {
                    $possibleDosenPaIds = $dosenByProdi->get($prodiId);
                    $dosenPaId = $possibleDosenPaIds[array_rand($possibleDosenPaIds)];
                } elseif (!empty($allActiveDosenIds)) {
                    $dosenPaId = $allActiveDosenIds[array_rand($allActiveDosenIds)];
                }

                $nimPrefixKey = $tahun . '-' . $fakultasId . '-' . $prodiId;
                $nimCounters[$nimPrefixKey] = ($nimCounters[$nimPrefixKey] ?? 0) + 1;
                $nomorUrut = $nimCounters[$nimPrefixKey];
                
                if ($fakultasId > 99) Log::warning("Format NIM mungkin salah: fakultas_id ($fakultasId) lebih dari 2 digit untuk NIM.");
                if ($prodiId > 999) Log::warning("Format NIM mungkin salah: prodi_id ($prodiId) lebih dari 3 digit untuk NIM.");
                
                $nim = sprintf("%04d%02d%03d%04d", $tahun, $fakultasId, $prodiId, $nomorUrut);

                // Data spesifik yang akan diisi oleh seeder
                $seederProvidedData = [
                    'nim' => $nim,
                    'tahun_masuk' => $tahun,
                    'prodi_id' => $prodiId,
                    'dosen_pa_id' => $dosenPaId,
                    'status' => 'Aktif',
                ];

                // Data yang akan dihasilkan oleh factory (menggunakan raw() agar dapat array)
                // Berikan $seederProvidedData agar factory bisa pakai 'tahun_masuk' jika perlu
                $factoryGeneratedData = Mahasiswa::factory()->raw($seederProvidedData);

                // GABUNGKAN data dari seeder (yang memiliki 'nim') dengan data dari factory
                // Data dari $seederProvidedData akan MENIMPA data dari $factoryGeneratedData jika ada key yang sama
                $fullMahasiswaData = array_merge($factoryGeneratedData, $seederProvidedData);
                
                $mahasiswaFullDataBatch[] = $fullMahasiswaData; // Tambahkan data yang sudah lengkap ke batch

                // Batch insert
                if (count($mahasiswaFullDataBatch) >= 1000) { // Ukuran batch
                    Mahasiswa::insert($mahasiswaFullDataBatch); // Gunakan insert() untuk performa tinggi dengan array data
                    // Jika Anda tetap ingin menggunakan factory dengan event dll, tapi lebih lambat:
                    // Mahasiswa::factory()->createMany($mahasiswaFullDataBatch);
                    
                    $totalMahasiswaCreated += count($mahasiswaFullDataBatch);
                    $mahasiswaFullDataBatch = []; // Reset batch
                    $progressBar->advance(1000);
                }
            }

            // Insert sisa data dalam batch terakhir
            if (!empty($mahasiswaFullDataBatch)) {
                $batchCount = count($mahasiswaFullDataBatch);
                Mahasiswa::insert($mahasiswaFullDataBatch); // Gunakan insert()
                // Atau: Mahasiswa::factory()->createMany($mahasiswaFullDataBatch);
                
                $totalMahasiswaCreated += $batchCount;
                $progressBar->advance($batchCount);
            }
        }

        $progressBar->finish();
        $this->command->info("\nTotal Mahasiswa yang berhasil di-seed: " . $totalMahasiswaCreated);
    }
}