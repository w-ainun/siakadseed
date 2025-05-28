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
    protected $batchSize = 2000; // Meningkatkan ukuran batch untuk IPS

    public function run()
    {
        $this->command->info('Memulai seeding data IPS dan IPK...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('ips')->truncate();
        DB::table('ipk')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $mahasiswas = Mahasiswa::all();
        $tahunAkademiks = TahunAkademik::orderBy('tahun_akademik')->orderByRaw("FIELD(semester, 'Ganjil', 'Genap')")->get()->keyBy('id_tahunakademik');

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
        $ipkToUpsert = []; // Akan menggunakan upsert

        // Load semua data yang relevan di awal untuk mengurangi query dalam loop
        $allKrs = Krs::select('id_krs', 'mahasiswa_id', 'tahun_akademik_id')->get()->groupBy('mahasiswa_id');
        $allKrsDetails = KrsDetail::with('nilaiAkhir', 'kelas.mataKuliah')->get()->groupBy('krs_id');

        foreach ($mahasiswas as $mahasiswa) {
            $progressBar->setMessage("Processing IPS/IPK for Mahasiswa: {$mahasiswa->nama}");

            $tahunMasuk = (int)$mahasiswa->tahun_masuk;
            $relevantTahunAkademiks = $tahunAkademiks->filter(function ($ta) use ($tahunMasuk) {
                $taStartYear = (int)explode('/', $ta->tahun_akademik)[0];
                return $taStartYear >= $tahunMasuk;
            });

            $currentStudentTotalSksKumulatif = 0;
            $currentStudentTotalBobotNilaiKumulatif = 0;

            foreach ($relevantTahunAkademiks as $ta) {
                // Temukan KRS menggunakan data yang sudah dimuat
                $krsForStudent = $allKrs->get($mahasiswa->nim)?->firstWhere('tahun_akademik_id', $ta->id_tahunakademik);

                if ($krsForStudent) {
                    $krsDetailsForKrs = $allKrsDetails->get($krsForStudent->id_krs);

                    $currentSemesterBobotNilai = 0;
                    $currentSemesterSks = 0;

                    if ($krsDetailsForKrs) {
                        foreach ($krsDetailsForKrs as $krsDetail) {
                            if ($krsDetail->nilaiAkhir && $krsDetail->kelas && $krsDetail->kelas->mataKuliah) {
                                $sksMk = $krsDetail->kelas->mataKuliah->sks;
                                $gradeValue = $this->letterGrades[$krsDetail->nilaiAkhir->nilai_huruf] ?? 0;

                                $currentSemesterBobotNilai += ($gradeValue * $sksMk);
                                $currentSemesterSks += $sksMk;
                            }
                        }
                    }

                    $ipsValue = ($currentSemesterSks > 0) ? round($currentSemesterBobotNilai / $currentSemesterSks, 2) : 0;

                    $ipsToInsert[] = [
                        'mahasiswa_id' => $mahasiswa->nim,
                        'tahun_akademik_id' => $ta->id_tahunakademik,
                        'ips' => $ipsValue,
                        'total_sks' => $currentSemesterSks,
                        'created_at' => now()->format('Y-m-d H:i:s'),
                        'updated_at' => now()->format('Y-m-d H:i:s'),
                    ];
                    
                    $currentStudentTotalSksKumulatif += $currentSemesterSks;
                    $currentStudentTotalBobotNilaiKumulatif += $currentSemesterBobotNilai;
                }
            }

            // Setelah memproses semua semester yang relevan untuk satu mahasiswa, hitung IPK
            $ipkValue = ($currentStudentTotalSksKumulatif > 0) ? round($currentStudentTotalBobotNilaiKumulatif / $currentStudentTotalSksKumulatif, 2) : 0;

            $ipkToUpsert[] = [
                'mahasiswa_id' => $mahasiswa->nim,
                'ipk' => $ipkValue,
                'total_sks' => $currentStudentTotalSksKumulatif,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ];

            $progressBar->advance();
        }
        $progressBar->finish();

        $this->command->info("\nInserting IPS data in batches...");
        $progressBar = $this->command->getOutput()->createProgressBar(count($ipsToInsert));
        $progressBar->start();

        // Insert semua IPS data dalam batch
        foreach (array_chunk($ipsToInsert, $this->batchSize) as $chunk) {
            try {
                Ips::insert($chunk);
            } catch (\Illuminate\Database\QueryException $e) {
                if (!str_contains($e->getMessage(), 'ips_mahasiswa_id_tahun_akademik_id_unique')) {
                    Log::error("Error inserting IPS batch: " . $e->getMessage());
                    throw $e;
                } else {
                    Log::warning("Duplicate IPS entry detected for batch. Skipping duplicates.");
                }
            }
            $progressBar->advance(count($chunk));
        }
        $progressBar->finish();
        
        $this->command->info("\nUpserting IPK data...");
        // Gunakan upsert untuk IPK karena setiap mahasiswa hanya punya satu record
        if (!empty($ipkToUpsert)) {
            Ipk::upsert(
                $ipkToUpsert,
                ['mahasiswa_id'], // Unique by mahasiswa_id
                ['ipk', 'total_sks', 'updated_at'] // Fields to update
            );
        }

        $this->command->info("\nSeeding IPS dan IPK selesai.");
    }
}