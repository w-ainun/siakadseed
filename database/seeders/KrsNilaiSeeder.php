<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;
use App\Models\Kelas;
// use App\Models\Krs; // Not directly used for mass insertion, but model exists
// use App\Models\KrsDetail; // Not directly used for mass insertion
// use App\Models\KomponenNilai; // Not directly used for mass insertion
// use App\Models\Nilai; // Not directly used for mass insertion
// use App\Models\NilaiAkhir; // Not directly used for mass insertion
use Illuminate\Support\Facades\DB;
use Faker\Factory as FakerFactory;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str; // For UUIDs

class KrsNilaiSeeder extends Seeder
{
    protected $faker;

    protected $nilaiHurufMapping = [
        85 => 'A',
        80 => 'A-',
        75 => 'B+',
        70 => 'B',
        65 => 'B-',
        60 => 'C+',
        55 => 'C',
        50 => 'C-',
        45 => 'D',
        0 => 'E',
    ];

    // Configuration for data generation scaling
    protected $maxKrsPerStudentPerYear = 1; // Limit 1 KRS per student per academic year
    protected $maxKelasPerKrs = 8; // Max classes per KRS
    protected $minKelasPerKrs = 5; // Min classes per KRS
    protected $batchSize = 2000; // Optimal batch size (adjust based on memory/DB limits)

    public function __construct()
    {
        $this->faker = FakerFactory::create('id_ID');
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting KRS and Nilai Seeding (Highly Optimized)...');

        // Increase memory limit and execution time if necessary
        ini_set('memory_limit', '2048M'); // Increased memory for potentially large datasets
        ini_set('max_execution_time', 7200); // 2 hours (120 minutes)

        // Disable query log to save memory and improve performance for large inserts
        if (DB::connection()->logging()) {
            DB::connection()->disableQueryLog();
        }
        
        // Temporarily disable foreign key checks for faster inserts, re-enable at the end
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->command->info('Fetching essential data...');
        $mahasiswas = Mahasiswa::select('nim', 'tahun_masuk', 'prodi_id')->get();
        $tahunAkademiks = TahunAkademik::orderBy('tahun_akademik')
                                        ->orderByRaw("FIELD(semester, 'Ganjil', 'Genap')")
                                        ->get();
        $activeTahunAkademik = TahunAkademik::where('is_active', true)->first();

        // Pre-fetch all necessary Kelas data
        $kelasCollection = Kelas::with('mataKuliah:kode_matakuliah,sks')
                                ->where('is_active', true)
                                ->get()
                                ->groupBy('tahun_akademik_id'); // Group by TA for faster lookup

        // Pre-fetch existing KRS entries to avoid duplicates (using actual IDs for robustness)
        $existingKrsMap = DB::table('krs')
                            ->select('mahasiswa_id', 'tahun_akademik_id')
                            ->get()
                            ->mapWithKeys(fn($item) => ["{$item->mahasiswa_id}-{$item->tahun_akademik_id}" => true]);


        if ($mahasiswas->isEmpty()) {
            $this->command->warn('No Mahasiswa found. Skipping KRS and Nilai seeding.');
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return;
        }
        if ($tahunAkademiks->isEmpty()) {
            $this->command->warn('No Tahun Akademik found. Skipping KRS and Nilai seeding.');
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            return;
        }
        if (!$activeTahunAkademik) {
            $this->command->warn('No active Tahun Akademik found. Consider running MasterDataSeeder first. Skipping KRS for active semester.');
        }

        $krsToProcessCount = 0;
        // Calculate the number of potential KRS entries to set up the progress bar
        foreach ($mahasiswas as $mahasiswa) {
            $mahasiswaStartYear = (int) $mahasiswa->tahun_masuk;
            $semesterOrder = ['Ganjil' => 1, 'Genap' => 2];
            $mahasiswaStartTaOrder = $mahasiswaStartYear * 10 + $semesterOrder['Ganjil'];

            foreach ($tahunAkademiks as $ta) {
                $taStartYear = (int) explode('/', $ta->tahun_akademik)[0];
                $currentTaOrder = $taStartYear * 10 + $semesterOrder[$ta->semester];

                if ($currentTaOrder < $mahasiswaStartTaOrder) {
                    continue;
                }
                
                // Only count if a KRS for this student and TA doesn't already exist
                if (!$existingKrsMap->has("{$mahasiswa->nim}-{$ta->id_tahunakademik}")) {
                    // Check if current TA is not in the future beyond active TA
                    $isCurrentOrFutureTA = false;
                    if ($activeTahunAkademik) {
                        $activeTaStartYear = (int) explode('/', $activeTahunAkademik->tahun_akademik)[0];
                        $activeTaOrder = $activeTaStartYear * 10 + $semesterOrder[$activeTahunAkademik->semester];
                        if ($currentTaOrder > $activeTaOrder) {
                            continue; // Skip future TAs beyond active one
                        }
                    }
                    $krsToProcessCount++;
                }
            }
        }

        // Initialize the progress bar
        $progressBar = $this->command->getOutput()->createProgressBar($krsToProcessCount);
        $progressBar->start();

        // Initialize arrays to hold data for batch insertion
        $allKrsData = [];
        $allKrsDetailData = [];
        $allKomponenNilaiData = [];
        $allNilaiData = [];
        $allNilaiAkhirData = [];

        // Maps to store temporary UUIDs to actual auto-incrementing IDs after insertion
        $krsTempToActualIdMap = [];
        $krsDetailTempToActualIdMap = [];
        $komponenNilaiTempToActualIdMap = [];

        $semesterOrder = ['Ganjil' => 1, 'Genap' => 2];
        foreach ($mahasiswas as $mahasiswa) {
            $mahasiswaStartYear = (int) $mahasiswa->tahun_masuk;
            $mahasiswaProdiId = $mahasiswa->prodi_id; // Get prodi_id once per mahasiswa
            $mahasiswaStartTaOrder = $mahasiswaStartYear * 10 + $semesterOrder['Ganjil'];

            foreach ($tahunAkademiks as $ta) {
                $taStartYear = (int) explode('/', $ta->tahun_akademik)[0];
                $currentTaOrder = $taStartYear * 10 + $semesterOrder[$ta->semester];

                if ($currentTaOrder < $mahasiswaStartTaOrder) {
                    continue;
                }

                $isCurrentOrFutureTA = false;
                if ($activeTahunAkademik) {
                    $activeTaStartYear = (int) explode('/', $activeTahunAkademik->tahun_akademik)[0];
                    $activeTaOrder = $activeTaStartYear * 10 + $semesterOrder[$activeTahunAkademik->semester];
                    if ($currentTaOrder > $activeTaOrder) {
                        continue;
                    }
                    if ($currentTaOrder === $activeTaOrder) {
                        $isCurrentOrFutureTA = true;
                    }
                }

                // Skip if KRS already exists (checked earlier for progress bar, now for actual processing)
                if ($existingKrsMap->has("{$mahasiswa->nim}-{$ta->id_tahunakademik}")) {
                    $progressBar->advance();
                    continue;
                }
                
                // Get available classes for this TA and Mahasiswa's Prodi
                // Use the pre-fetched and grouped $kelasCollection
                $availableKelasForTA = $kelasCollection->get($ta->id_tahunakademik);

                if ($availableKelasForTA === null || $availableKelasForTA->isEmpty()) {
                    $progressBar->advance();
                    continue;
                }

                // Filter kelas by prodi_id (if mataKuliah.kurikulum relation exists)
                $availableKelas = $availableKelasForTA->filter(function($kelas) use ($mahasiswaProdiId) {
                    // Check if mataKuliah and kurikulum relation exist
                    return $kelas->mataKuliah && $kelas->mataKuliah->kurikulum && 
                                 $kelas->mataKuliah->kurikulum->prodi_id === $mahasiswaProdiId &&
                                 $kelas->mataKuliah->kurikulum->is_active;
                });
                
                if ($availableKelas->isEmpty()) {
                    $progressBar->advance();
                    continue;
                }

                $tanggalPengajuan = Carbon::parse($ta->tanggal_mulai)->addDays($this->faker->numberBetween(0, 14));
                $statusKRS = 'Disetujui'; // Default to approved for past semesters
                if ($isCurrentOrFutureTA) {
                    $statusKRS = $this->faker->randomElement(['Diajukan', 'Draft']); // Only Diajukan/Draft for current/future active TA
                }
                $tanggalPersetujuan = ($statusKRS === 'Disetujui') ? $tanggalPengajuan->copy()->addDays($this->faker->numberBetween(1, 5)) : null;

                $krsTempId = (string) Str::uuid(); // Use UUID for temporary ID for internal linking

                $krsData = [
                    'temp_id' => $krsTempId, // Store temporary ID for later mapping in PHP
                    'mahasiswa_id' => $mahasiswa->nim,
                    'tahun_akademik_id' => $ta->id_tahunakademik,
                    'tanggal_pengajuan' => $tanggalPengajuan,
                    'tanggal_persetujuan' => $tanggalPersetujuan,
                    'status' => $statusKRS,
                    'catatan' => ($this->faker->boolean(10) && $statusKRS === 'Ditolak') ? $this->faker->sentence() : null,
                    'created_at' => $tanggalPengajuan,
                    'updated_at' => $tanggalPersetujuan ?? $tanggalPengajuan,
                    'total_sks' => 0, // Will be calculated and updated
                    'temp_seed_uuid' => $krsTempId, // Store UUID in DB for mapping
                ];
                $allKrsData[] = $krsData;

                $jumlahMkDiambil = $this->faker->numberBetween($this->minKelasPerKrs, $this->maxKelasPerKrs);
                $selectedKelas = $availableKelas->shuffle()->take($jumlahMkDiambil);

                $currentKrsSks = 0;
                foreach ($selectedKelas as $kelas) {
                    // Make sure $kelas->mataKuliah exists before accessing properties
                    $sks = $kelas->mataKuliah->sks ?? 0;
                    $currentKrsSks += $sks;

                    $krsDetailTempId = (string) Str::uuid(); // Use UUID for temporary ID

                    $krsDetailData = [
                        'temp_id' => $krsDetailTempId, // Temporary ID for this KrsDetail in PHP
                        'krs_id_temp' => $krsTempId, // Link to the temporary KRS ID in PHP
                        'kelas_id' => $kelas->id_kelas,
                        'created_at' => $tanggalPengajuan,
                        'updated_at' => $tanggalPengajuan,
                        'temp_seed_uuid' => $krsDetailTempId, // Store UUID in DB for mapping
                    ];
                    $allKrsDetailData[] = $krsDetailData;

                    if ($statusKRS === 'Disetujui' && !$isCurrentOrFutureTA) {
                        $this->prepareGradesForKrsDetail($krsDetailTempId, $kelas, $allKomponenNilaiData, $allNilaiData, $allNilaiAkhirData, $tanggalPengajuan);
                    }
                }
                // Update total_sks for the last KRS entry (which is the one just added)
                $lastKrsIndex = count($allKrsData) - 1;
                if ($lastKrsIndex >= 0) { // Ensure there's a KRS entry to update
                    $allKrsData[$lastKrsIndex]['total_sks'] = $currentKrsSks;
                }
                
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->command->info("\nAll data collected. Starting batch insertions...");

        // --- Perform Batch Inserts in a Transaction ---
        DB::transaction(function () use (
            &$allKrsData,
            &$allKrsDetailData,
            &$allKomponenNilaiData,
            &$allNilaiData,
            &$allNilaiAkhirData,
            &$krsTempToActualIdMap,
            &$krsDetailTempToActualIdMap,
            &$komponenNilaiTempToActualIdMap
        ) {
            // 1. Insert KRS data
            $this->command->info("Inserting KRS records in batches...");
            $krsInsertableData = array_map(function($item) {
                return [
                    'mahasiswa_id' => $item['mahasiswa_id'],
                    'tahun_akademik_id' => $item['tahun_akademik_id'],
                    'tanggal_pengajuan' => $item['tanggal_pengajuan'],
                    'tanggal_persetujuan' => $item['tanggal_persetujuan'],
                    'status' => $item['status'],
                    'catatan' => $item['catatan'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                    'total_sks' => $item['total_sks'],
                    'temp_seed_uuid' => $item['temp_id'], // Now using temp_id for mapping in DB
                ];
            }, $allKrsData);

            $this->chunkInsert(DB::table('krs'), $krsInsertableData, $this->batchSize);
            
            // Re-fetch IDs for mapping: This is the critical optimization point.
            $this->command->info("Mapping KRS IDs...");
            $krsUuids = array_column($allKrsData, 'temp_id');
            $actualKrsRecords = DB::table('krs')
                                ->select('id_krs', 'temp_seed_uuid')
                                ->whereIn('temp_seed_uuid', $krsUuids)
                                ->get();
            foreach ($actualKrsRecords as $record) {
                $krsTempToActualIdMap[$record->temp_seed_uuid] = $record->id_krs;
            }
            // Clear memory for no longer needed data
            unset($krsInsertableData);
            unset($actualKrsRecords);
            unset($krsUuids);
            $this->command->info("KRS records inserted and mapped.");

            // 2. Prepare and Insert KrsDetail data
            $this->command->info("Preparing and inserting KRS Detail records...");
            $krsDetailInsertableData = [];
            $krsDetailUuids = [];
            foreach ($allKrsDetailData as $item) {
                $actualKrsId = $krsTempToActualIdMap[$item['krs_id_temp']] ?? null;
                if ($actualKrsId) {
                    $krsDetailInsertableData[] = [
                        'krs_id' => $actualKrsId,
                        'kelas_id' => $item['kelas_id'],
                        'created_at' => $item['created_at'],
                        'updated_at' => $item['updated_at'],
                        'temp_seed_uuid' => $item['temp_id'], // Now using temp_id for mapping in DB
                    ];
                    $krsDetailUuids[] = $item['temp_id'];
                }
            }
            
            $this->chunkInsert(DB::table('krs_detail'), $krsDetailInsertableData, $this->batchSize);

            // Re-fetch IDs for mapping
            $this->command->info("Mapping KRS Detail IDs...");
            $actualKrsDetailRecords = DB::table('krs_detail')
                                        ->select('id_krsdetail', 'temp_seed_uuid')
                                        ->whereIn('temp_seed_uuid', $krsDetailUuids)
                                        ->get();
            foreach ($actualKrsDetailRecords as $record) {
                $krsDetailTempToActualIdMap[$record->temp_seed_uuid] = $record->id_krsdetail;
            }
            unset($krsDetailInsertableData);
            unset($actualKrsDetailRecords);
            unset($krsDetailUuids);
            $this->command->info("KRS Detail records inserted and mapped.");

            // 3. Prepare and Insert KomponenNilai data
            $this->command->info("Preparing and inserting Komponen Nilai records...");
            $komponenNilaiInsertableData = [];
            $komponenNilaiUuids = [];
            foreach ($allKomponenNilaiData as $item) {
                $komponenNilaiInsertableData[] = [
                    'kelas_id' => $item['kelas_id'],
                    'nama_komponen' => $item['nama_komponen'],
                    'bobot' => $item['bobot'],
                    'created_at' => $item['created_at'],
                    'updated_at' => $item['updated_at'],
                    'temp_seed_uuid' => $item['temp_id'], // Now using temp_id for mapping in DB
                ];
                $komponenNilaiUuids[] = $item['temp_id'];
            }

            $this->chunkInsert(DB::table('komponen_nilai'), $komponenNilaiInsertableData, $this->batchSize);

            // Re-fetch IDs for mapping
            $this->command->info("Mapping Komponen Nilai IDs...");
            $actualKomponenNilaiRecords = DB::table('komponen_nilai')
                                            ->select('id_komponennilai', 'temp_seed_uuid')
                                            ->whereIn('temp_seed_uuid', $komponenNilaiUuids)
                                            ->get();
            foreach ($actualKomponenNilaiRecords as $record) {
                $komponenNilaiTempToActualIdMap[$record->temp_seed_uuid] = $record->id_komponennilai;
            }
            unset($komponenNilaiInsertableData);
            unset($actualKomponenNilaiRecords);
            unset($komponenNilaiUuids);
            $this->command->info("Komponen Nilai records inserted and mapped.");

            // 4. Prepare and Insert Nilai data
            $this->command->info("Preparing and inserting Nilai records...");
            $nilaiInsertableData = [];
            foreach ($allNilaiData as $nilai) {
                $actualKrsDetailId = $krsDetailTempToActualIdMap[$nilai['krs_detail_id']] ?? null;
                $actualKomponenNilaiId = $komponenNilaiTempToActualIdMap[$nilai['komponen_nilai_id']] ?? null;
                if ($actualKrsDetailId && $actualKomponenNilaiId) {
                    $nilaiInsertableData[] = [
                        'krs_detail_id' => $actualKrsDetailId,
                        'komponen_nilai_id' => $actualKomponenNilaiId,
                        'nilai_angka' => $nilai['nilai_angka'],
                        'created_at' => $nilai['created_at'],
                        'updated_at' => $nilai['updated_at'],
                    ];
                }
            }
            $this->chunkInsert(DB::table('nilai'), $nilaiInsertableData, $this->batchSize);
            unset($nilaiInsertableData);
            $this->command->info("Nilai records inserted.");

            // 5. Prepare and Insert NilaiAkhir data
            $this->command->info("Preparing and inserting Nilai Akhir records...");
            $nilaiAkhirInsertableData = [];
            foreach ($allNilaiAkhirData as $nilaiAkhir) {
                $actualKrsDetailId = $krsDetailTempToActualIdMap[$nilaiAkhir['krs_detail_id']] ?? null;
                if ($actualKrsDetailId) {
                    $nilaiAkhirInsertableData[] = [
                        'krs_detail_id' => $actualKrsDetailId,
                        'nilai_angka' => $nilaiAkhir['nilai_angka'],
                        'nilai_huruf' => $nilaiAkhir['nilai_huruf'],
                        'created_at' => $nilaiAkhir['created_at'],
                        'updated_at' => $nilaiAkhir['updated_at'],
                    ];
                }
            }
            $this->chunkInsert(DB::table('nilai_akhir'), $nilaiAkhirInsertableData, $this->batchSize);
            unset($allKrsData); // Clear all collected data to free memory
            unset($allKrsDetailData);
            unset($allKomponenNilaiData);
            unset($allNilaiData);
            unset($allNilaiAkhirData);
            unset($nilaiAkhirInsertableData);
            $this->command->info("Nilai Akhir records inserted.");
        });

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->command->info("\nKRS and Nilai Seeding Completed!");
    }

    protected function prepareGradesForKrsDetail(
        $krsDetailTempId,
        Kelas $kelas,
        &$allKomponenNilaiData,
        &$allNilaiData,
        &$allNilaiAkhirData,
        $krsDetailCreatedAt
    ) {
        $komponenNilaiNames = ['UTS', 'UAS', 'Tugas', 'Kuis', 'Kehadiran'];
        $komponenNilaiWeights = [];
        $remainingWeight = 100;

        shuffle($komponenNilaiNames);

        // Assign weights ensuring they sum to 100
        foreach ($komponenNilaiNames as $index => $name) {
            if ($index === count($komponenNilaiNames) - 1) {
                $komponenNilaiWeights[$name] = $remainingWeight;
            } else {
                $weight = $this->faker->numberBetween(10, min(40, $remainingWeight));
                $komponenNilaiWeights[$name] = $weight;
                $remainingWeight -= $weight;
            }
        }

        $totalScore = 0;
        foreach ($komponenNilaiWeights as $name => $bobot) {
            $komponenNilaiTempId = (string) Str::uuid(); // Use UUID for temporary ID

            $allKomponenNilaiData[] = [
                'temp_id' => $komponenNilaiTempId, // Temporary ID for mapping in PHP
                'kelas_id' => $kelas->id_kelas,
                'nama_komponen' => $name,
                'bobot' => $bobot,
                'created_at' => $krsDetailCreatedAt,
                'updated_at' => $krsDetailCreatedAt,
                'temp_seed_uuid' => $komponenNilaiTempId, // Store UUID in DB for mapping
            ];

            $nilaiAngka = $this->faker->numberBetween(50, 95);
            $allNilaiData[] = [
                'krs_detail_id' => $krsDetailTempId, // Link to temporary KrsDetail ID (for PHP)
                'komponen_nilai_id' => $komponenNilaiTempId, // Link to temporary KomponenNilai ID (for PHP)
                'nilai_angka' => $nilaiAngka,
                'created_at' => $krsDetailCreatedAt->copy()->addDays($this->faker->numberBetween(10, 60)), // Use copy to avoid modifying original Carbon instance
                'updated_at' => $krsDetailCreatedAt->copy()->addDays($this->faker->numberBetween(10, 60)),
            ];

            $totalScore += ($nilaiAngka * ($bobot / 100));
        }

        $nilaiAkhirAngka = round($totalScore, 2);
        $nilaiAkhirHuruf = $this->convertScoreToGrade($nilaiAkhirAngka);

        $allNilaiAkhirData[] = [
            'krs_detail_id' => $krsDetailTempId, // Link to temporary KrsDetail ID (for PHP)
            'nilai_angka' => $nilaiAkhirAngka,
            'nilai_huruf' => $nilaiAkhirHuruf,
            'created_at' => $krsDetailCreatedAt->copy()->addDays($this->faker->numberBetween(70, 90)),
            'updated_at' => $krsDetailCreatedAt->copy()->addDays($this->faker->numberBetween(70, 90)),
        ];
    }

    protected function convertScoreToGrade($score)
    {
        foreach ($this->nilaiHurufMapping as $minScore => $grade) {
            if ($score >= $minScore) {
                return $grade;
            }
        }
        return 'E';
    }

    /**
     * Chunks and inserts data into the database.
     *
     * @param \Illuminate\Database\Query\Builder $queryBuilder
     * @param array $data
     * @param int $chunkSize
     * @return void
     */
    protected function chunkInsert($queryBuilder, array $data, int $chunkSize = 1000)
    {
        if (empty($data)) {
            return;
        }
        $tableName = $queryBuilder->from; // Get table name for logging
        $totalChunks = ceil(count($data) / $chunkSize);
        $this->command->info("Inserting " . count($data) . " records into {$tableName} in {$totalChunks} chunks.");

        foreach (array_chunk($data, $chunkSize) as $index => $chunk) {
            try {
                $queryBuilder->insert($chunk);
                // Optional: Progress indicator for large inserts
                // $this->command->comment("Chunk " . ($index + 1) . "/" . $totalChunks . " of {$tableName} inserted.");
            } catch (Exception $e) {
                $this->command->error("Error inserting chunk " . ($index + 1) . " into {$tableName}: " . $e->getMessage());
                $this->command->error("First item in failed chunk (for debugging): " . json_encode(head($chunk))); // Display first item for debugging
                // Throw the exception to stop seeding and show error
                throw $e; 
            }
        }
    }
}