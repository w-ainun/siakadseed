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
        'A' => 4.00, 'B+' => 3.50, 'B' => 3.00, 'C+' => 2.50,
        'C' => 2.00, 'D+' => 1.50, 'D' => 1.00, 'E' => 0.00
    ];
    protected $componentNames = ['Kehadiran', 'Tugas', 'UTS', 'UAS'];
    protected $componentWeights = [
        'Kehadiran' => 10, 'Tugas' => 30, 'UTS' => 30, 'UAS' => 30
    ];
    protected $batchSize = 1000;

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
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $allKelasIds = Kelas::pluck('id_kelas')->toArray();
        $komponenNilaiToInsert = [];

        $existingKomponenNilai = KomponenNilai::all()->groupBy('kelas_id');

        foreach ($allKelasIds as $kelasId) {
            $currentClassComponents = $existingKomponenNilai->get($kelasId, collect());
            foreach ($this->componentNames as $compName) {
                $found = $currentClassComponents->first(fn($kn) => $kn->nama_komponen === $compName);
                if (!$found) {
                    $komponenNilaiToInsert[] = [
                        'kelas_id' => $kelasId,
                        'nama_komponen' => $compName,
                        'bobot' => $this->componentWeights[$compName],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        if (!empty($komponenNilaiToInsert)) {
            $this->command->info("Inserting new KomponenNilai in batches...");
            foreach (array_chunk($komponenNilaiToInsert, $this->batchSize) as $chunk) {
                KomponenNilai::insert($chunk);
            }
        }

        $komponenNilaiCache = [];
        foreach (KomponenNilai::all() as $kn) {
            $komponenNilaiCache[$kn->kelas_id][] = $kn;
        }

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
                Log::warning("Kelas atau Komponen Nilai tidak ditemukan untuk KRS Detail ID: {$krsDetail->id_krsdetail}. Dilewati.");
                continue;
            }

            $totalNilaiAngka = 0;
            foreach ($komponenNilaiCache[$kelas->id_kelas] as $komponen) {
                $nilaiAngka = $this->faker->numberBetween(40, 100);
                $nilaiToInsert[] = [
                    'krs_detail_id' => $krsDetail->id_krsdetail,
                    'komponen_nilai_id' => $komponen->id_komponennilai,
                    'nilai_angka' => $nilaiAngka,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $totalNilaiAngka += ($nilaiAngka * ($komponen->bobot / 100));
            }

            $finalNilaiAngka = round($totalNilaiAngka, 2);
            $nilaiHuruf = iconv('UTF-8', 'UTF-8//IGNORE', $this->convertToLetterGrade($finalNilaiAngka));


            $nilaiAkhirToInsert[] = [
                'krs_detail_id' => $krsDetail->id_krsdetail,
                'nilai_angka' => $finalNilaiAngka,
                'nilai_huruf' => $nilaiHuruf,
                'created_at' => now(),
                'updated_at' => now(),
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
        $this->command->info("\nSeeding data Nilai selesai.");
    }

    protected function convertToLetterGrade(float $nilaiAngka): string
    {
        // Urutkan berdasarkan nilai tertinggi ke terendah
        $grades = collect($this->letterGrades)->sortDesc();

        foreach ($grades as $huruf => $gpa) {
            if ($nilaiAngka >= $gpa * 25) {
                return $huruf;
            }
        }

        return 'E';
    }
}
