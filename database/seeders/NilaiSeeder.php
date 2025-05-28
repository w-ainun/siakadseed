<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KrsDetail;
use App\Models\KomponenNilai;
use App\Models\Nilai;
use App\Models\NilaiAkhir;
use App\Models\Kelas;
use Illuminate\Support\Facades\DB;
use Faker\Factory as FakerFactory;
use Illuminate\Support\Facades\Log;

class NilaiSeeder extends Seeder
{
    protected $faker;
    protected $letterGrades = [
        'A' => 4.00, 'AB' => 3.50, 'B' => 3.00, 'BC' => 2.50,
        'C' => 2.00, 'D' => 1.00, 'E' => 0.00
    ];
    protected $componentNames = ['Kehadiran', 'Tugas', 'UTS', 'UAS'];
    protected $componentWeights = [
        'Kehadiran' => 10, 'Tugas' => 30, 'UTS' => 30, 'UAS' => 30
    ];
    protected $batchSize = 2000; // Meningkatkan ukuran batch

    public function __construct()
    {
        $this->faker = FakerFactory::create('id_ID');
    }

    public function run()
    {
        $this->command->info('Memulai seeding data Nilai...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('nilai')->truncate();
        DB::table('nilai_akhir')->truncate();
        // Jangan truncate komponen_nilai jika sudah dibuat di seeder lain
        // DB::table('komponen_nilai')->truncate(); 
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Persiapan KomponenNilai untuk semua Kelas yang ada
        $allKelasIds = Kelas::pluck('id_kelas')->toArray();
        $komponenNilaiCache = [];
        $komponenNilaiToInsert = [];

        // Load existing KomponenNilai
        $existingKomponenNilai = KomponenNilai::all()->groupBy('kelas_id');

        foreach ($allKelasIds as $kelasId) {
            $currentClassComponents = $existingKomponenNilai->get($kelasId, collect());
            foreach ($this->componentNames as $compName) {
                // Check if component already exists for this class
                $found = $currentClassComponents->first(fn($kn) => $kn->nama_komponen === $compName);
                if (!$found) {
                    // Prepare for batch insert if not found
                    $komponenNilaiToInsert[] = [
                        'kelas_id' => $kelasId,
                        'nama_komponen' => $compName,
                        'bobot' => $this->componentWeights[$compName],
                        'created_at' => now()->format('Y-m-d H:i:s'),
                        'updated_at' => now()->format('Y-m-d H:i:s'),
                    ];
                }
            }
        }
        
        // Insert new KomponenNilai in batch
        if (!empty($komponenNilaiToInsert)) {
            $this->command->info("Inserting new KomponenNilai in batches...");
            foreach (array_chunk($komponenNilaiToInsert, $this->batchSize) as $chunk) {
                KomponenNilai::insert($chunk);
            }
        }
        
        // Re-cache all KomponenNilai after potential inserts
        $komponenNilaiCache = [];
        foreach (KomponenNilai::all() as $kn) {
            $komponenNilaiCache[$kn->kelas_id][] = $kn;
        }

        // 2. Ambil semua KRS Detail dengan eager loading untuk performance
        // Asumsi KRS Detail sudah ada dari KrsSeeder
        $krsDetails = KrsDetail::with(['kelas.mataKuliah'])->get();
        
        if ($krsDetails->isEmpty()) {
            $this->command->warn('Tidak ada data KRS Detail. Seeder Nilai dihentikan.');
            return;
        }

        $totalKrsDetails = $krsDetails->count();
        $progressBar = $this->command->getOutput()->createProgressBar($totalKrsDetails);
        $progressBar->setFormatDefinition('custom', ' %current%/%max% [%bar%] %percent:3s%% -- %message% (Est: %estimated:-6s% Left: %remaining:-6s%)');
        $progressBar->setFormat('custom');
        $progressBar->start();

        $nilaiToInsert = [];
        $nilaiAkhirToInsert = [];

        foreach ($krsDetails as $krsDetail) {
            $progressBar->setMessage("Processing Nilai for KRS Detail ID: {$krsDetail->id_krsdetail}");

            $kelas = $krsDetail->kelas;
            if (!$kelas || !isset($komponenNilaiCache[$kelas->id_kelas])) {
                // Ini seharusnya tidak terjadi jika persiapan KomponenNilai sudah benar
                Log::warning("Kelas atau KomponenNilai tidak ditemukan untuk KRS Detail ID: {$krsDetail->id_krsdetail}. Dilewati.");
                continue;
            }

            $totalNilaiAngka = 0;
            foreach ($komponenNilaiCache[$kelas->id_kelas] as $komponen) {
                $nilaiAngka = $this->faker->numberBetween(40, 100);
                $nilaiToInsert[] = [
                    'krs_detail_id' => $krsDetail->id_krsdetail,
                    'komponen_nilai_id' => $komponen->id_komponennilai,
                    'nilai_angka' => $nilaiAngka,
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                ];
                $totalNilaiAngka += ($nilaiAngka * ($komponen->bobot / 100));
            }

            $finalNilaiAngka = round($totalNilaiAngka, 2);
            $nilaiHuruf = 'E'; // Default
            foreach ($this->letterGrades as $grade => $gpa) {
                if ($finalNilaiAngka >= ($gpa * 25)) { 
                    $nilaiHuruf = $grade;
                    break;
                }
            }

            $nilaiAkhirToInsert[] = [
                'krs_detail_id' => $krsDetail->id_krsdetail,
                'nilai_angka' => $finalNilaiAngka,
                'nilai_huruf' => $nilaiHuruf,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => now()->format('Y-m-d H:i:s'),
            ];

            if (count($nilaiToInsert) >= $this->batchSize) {
                Nilai::insert($nilaiToInsert);
                $nilaiToInsert = [];
            }
            if (count($nilaiAkhirToInsert) >= $this->batchSize) {
                NilaiAkhir::insert($nilaiAkhirToInsert);
                $nilaiAkhirToInsert = [];
            }
            $progressBar->advance();
        }

        if (!empty($nilaiToInsert)) {
            Nilai::insert($nilaiToInsert);
        }
        if (!empty($nilaiAkhirToInsert)) {
            NilaiAkhir::insert($nilaiAkhirToInsert);
        }

        $progressBar->finish();
        $this->command->info("\nSeeding Nilai selesai.");
    }
}