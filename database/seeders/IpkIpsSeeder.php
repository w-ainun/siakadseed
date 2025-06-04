<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\NilaiAkhir; 
use App\Models\Kelas;
use App\Models\Ips;
use App\Models\Ipk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IpkIpsSeeder extends Seeder
{
    protected $letterGrades = [
        'A' => 4.00, 'AB' => 3.50, 'B' => 3.00, 'BC' => 2.50,
        'C' => 2.00, 'D' => 1.00, 'E' => 0.00
    ];

    protected $batchSize = 2000;

    public function run()
    {
        $this->command->info('Memulai seeding data IPS dan IPK...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('ips')->truncate();
        DB::table('ipk')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $mahasiswas = Mahasiswa::all();
        $tahunAkademiks = TahunAkademik::orderBy('tahun_akademik')
            ->orderByRaw("FIELD(semester, 'Ganjil', 'Genap')")
            ->get()
            ->keyBy('id_tahunakademik');

        if ($mahasiswas->isEmpty() || $tahunAkademiks->isEmpty()) {
            $this->command->error('Data dasar (Mahasiswa, Tahun Akademik) tidak ditemukan. Seeder dihentikan.');
            return;
        }

        $totalMahasiswa = $mahasiswas->count();
        $progressBar = $this->command->getOutput()->createProgressBar($totalMahasiswa);
        $progressBar->setFormatDefinition('custom', ' %current%/%max% [%bar%] %percent:3s%% -- %message% (Est: %estimated:-6s% Left: %remaining:-6s%)');
        $progressBar->setFormat('custom');
        $progressBar->start();

        $ipsToInsert = [];
        $ipkToUpsert = [];

        $allKrs = Krs::select('id_krs', 'mahasiswa_id', 'tahun_akademik_id')->get()->groupBy('mahasiswa_id');
        $allKrsDetails = KrsDetail::with('nilaiAkhir', 'kelas.mataKuliah')->get()->groupBy('krs_id');

        foreach ($mahasiswas as $mahasiswa) {
            $progressBar->setMessage("Processing {$mahasiswa->nama}");

            $tahunMasuk = (int)$mahasiswa->tahun_masuk;
            $relevantTahunAkademiks = $tahunAkademiks->filter(function ($ta) use ($tahunMasuk) {
                $taStartYear = (int)explode('/', $ta->tahun_akademik)[0];
                return $taStartYear >= $tahunMasuk;
            });

            $totalSksKumulatif = 0;
            $totalBobotKumulatif = 0;

            foreach ($relevantTahunAkademiks as $ta) {
                $krsForStudent = $allKrs->get($mahasiswa->nim)?->firstWhere('tahun_akademik_id', $ta->id_tahunakademik);

                if ($krsForStudent) {
                    $krsDetails = $allKrsDetails->get($krsForStudent->id_krs);
                    $bobotNilai = 0;
                    $sksSemester = 0;

                    if ($krsDetails) {
                        foreach ($krsDetails as $detail) {
                            if ($detail->nilaiAkhir && $detail->kelas && $detail->kelas->mataKuliah) {
                                $sks = $detail->kelas->mataKuliah->sks;
                                $nilaiHuruf = $detail->nilaiAkhir->nilai_huruf;
                                $nilaiAngka = $this->letterGrades[$nilaiHuruf] ?? 0;

                                $bobotNilai += $sks * $nilaiAngka;
                                $sksSemester += $sks;
                            }
                        }
                    }

                    $ips = ($sksSemester > 0) ? round($bobotNilai / $sksSemester, 2) : 0;

                    $ipsToInsert[] = [
                        'mahasiswa_id' => $mahasiswa->nim,
                        'tahun_akademik_id' => $ta->id_tahunakademik,
                        'ips' => $ips,
                        'total_sks' => $sksSemester,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $totalSksKumulatif += $sksSemester;
                    $totalBobotKumulatif += $bobotNilai;
                }
            }

            $ipk = ($totalSksKumulatif > 0) ? round($totalBobotKumulatif / $totalSksKumulatif, 2) : 0;

            $ipkToUpsert[] = [
                'mahasiswa_id' => $mahasiswa->nim,
                'ipk' => $ipk,
                'total_sks' => $totalSksKumulatif,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $progressBar->advance();
        }

        $progressBar->finish();

        // Insert IPS
        $this->command->info("\nMenyimpan data IPS...");
        $progressBar = $this->command->getOutput()->createProgressBar(count($ipsToInsert));
        $progressBar->start();

        foreach (array_chunk($ipsToInsert, $this->batchSize) as $chunk) {
            try {
                Ips::insert($chunk);
            } catch (\Illuminate\Database\QueryException $e) {
                if (!str_contains($e->getMessage(), 'ips_mahasiswa_id_tahun_akademik_id_unique')) {
                    Log::error("Error inserting IPS: " . $e->getMessage());
                    throw $e;
                }
                Log::warning("Duplicate IPS entry skipped.");
            }
            $progressBar->advance(count($chunk));
        }

        $progressBar->finish();

        // Upsert IPK
        $this->command->info("\nMenyimpan data IPK...");

        if (empty($ipkToUpsert)) {
            $this->command->warn("Tidak ada data IPK yang dihitung. Cek apakah data nilai dan KRS tersedia.");
        } else {
            $this->command->info("Total data IPK yang akan di-upsert: " . count($ipkToUpsert));
            try {
                Ipk::upsert(
                    $ipkToUpsert,
                    ['mahasiswa_id'], // Harus unique di tabel ipk
                    ['ipk', 'total_sks', 'updated_at']
                );
                $this->command->info("Data IPK berhasil disimpan.");
            } catch (\Exception $e) {
                $this->command->error("Gagal menyimpan IPK: " . $e->getMessage());
                Log::error("IPK Upsert Error: " . $e->getMessage());
            }
        }

        $this->command->info("\nSeeding IPS dan IPK selesai.");
    }
}
