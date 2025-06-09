<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Krs;
use App\Models\KrsDetail;
use Illuminate\Support\Str;

class KrsDetailSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("🔍 Mengecek KRS yang belum memiliki detail...");

        // Ambil hanya KRS yang belum punya detail
        $krsTanpaDetail = Krs::whereDoesntHave('krsDetails')->get(); // pastikan relasi 'details' ada di model Krs
        $totalBaru = $krsTanpaDetail->count();
        $totalSebelumnya = KrsDetail::count();

        $this->command->info("ℹ️ Data KRS Detail saat ini: $totalSebelumnya entries.");
        $this->command->info("📦 Akan memproses $totalBaru KRS yang belum memiliki detail...");

        if ($totalBaru === 0) {
            $this->command->info("✅ Tidak ada KRS baru yang perlu diproses.");
            return;
        }

        $bar = $this->command->getOutput()->createProgressBar($totalBaru);
        $bar->setFormat("📦 %current%/%max% [%bar%] %percent:3s%% | ⏱️ %elapsed:6s% elapsed, ⌛ %estimated:-6s% left");
        $bar->start();

        $inserted = 0;

        foreach ($krsTanpaDetail as $krs) {
            $jumlahKelas = rand(4, 7); // Sesuai kebutuhan
            $kelasIds = [];

            while (count($kelasIds) < $jumlahKelas) {
                $randomId = rand(1, 1259);
                if (!in_array($randomId, $kelasIds)) {
                    $kelasIds[] = $randomId;
                }
            }

            foreach ($kelasIds as $kelasId) {
                KrsDetail::create([
                    'krs_id' => $krs->id_krs,
                    'kelas_id' => $kelasId,
                    'temp_seed_uuid' => Str::uuid()
                ]);
            }

            $krs->update(['total_sks' => $jumlahKelas * 3]); // Asumsikan 3 SKS

            $inserted++;
            $bar->advance();

            if ($inserted % 1000 === 0) {
                $this->command->info("📝 Ditambahkan $inserted KRS Detail baru.");
            }
        }

        $bar->finish();
        $this->command->newLine(2);
        $this->command->info("✅ Selesai menambahkan $inserted KRS Detail.");
        $this->command->info("📊 Total KRS Detail saat ini: " . KrsDetail::count());
    }
}
