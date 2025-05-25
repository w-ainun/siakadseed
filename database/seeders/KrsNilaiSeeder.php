<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;
use App\Models\MataKuliah;
use App\Models\Kelas;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\KomponenNilai;
use App\Models\Nilai;
use App\Models\NilaiAkhir;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;

class KrsNilaiSeeder extends Seeder
{
    protected $faker;
    protected $groupedMataKuliahCache = [];
    protected $availableKelasCache = [];
    protected $komponenNilaiCache = []; 

    public function __construct()
    {
        $this->faker = FakerFactory::create('id_ID');
    }

    public function run()
    {
        DB::disableQueryLog(); // Nonaktifkan query log di awal
        $this->command->info('Starting KRS and Nilai Seeding (Kelas are expected to exist)...');

        $allTahunAkademik = TahunAkademik::orderBy('tahun_akademik')->orderBy('semester')->get();
        $allMataKuliahRaw = MataKuliah::with('kurikulum.programStudi')->whereHas('kurikulum', function ($query) {
            $query->where('is_active', true);
        })->get();
        
        

        if ($allMataKuliahRaw->isEmpty() /*|| $dosenCount === 0*/ || $allTahunAkademik->isEmpty()) {
            $this->command->error("Master data (Mata Kuliah aktif/Tahun Akademik) is missing. Aborting KrsNilaiSeeder.");
            DB::enableQueryLog();
            return;
        }

        $this->groupedMataKuliahCache = $allMataKuliahRaw->filter(function ($mk) {
            return $mk->kurikulum && $mk->kurikulum->programStudi;
        })->groupBy(function ($mk) {
            return $mk->kurikulum->programStudi->id_prodi . '_' . $mk->semester;
        });
        unset($allMataKuliahRaw);

        $this->command->info('Pre-caching active Kelas...');
        $allRelevantKelas = Kelas::where('is_active', true)
            ->whereIn('tahun_akademik_id', $allTahunAkademik->pluck('id_tahunakademik')->all())
            ->get();

        foreach ($allRelevantKelas as $kelas) {
            $cacheKey = $kelas->tahun_akademik_id . '-' . $kelas->mata_kuliah_id; 
            if (!isset($this->availableKelasCache[$cacheKey])) {
                $this->availableKelasCache[$cacheKey] = collect();
            }
            $this->availableKelasCache[$cacheKey]->push($kelas);
        }
        unset($allRelevantKelas);
        $this->command->info('Kelas pre-caching finished.');


        $mahasiswaCount = Mahasiswa::count();
        $progressBar = $this->command->getOutput()->createProgressBar($mahasiswaCount);
        $progressBar->start();

        Mahasiswa::with('programStudi')->chunkById(300, function (Collection $mahasiswas) use ($allTahunAkademik, $progressBar) {
            
            $simulatedCurrentYear = 2026;
            $currentChunkTimestamp = Carbon::now();
            
            $krsContextCollection = []; 
            $komponenNilaiForChunkUpsert = []; 

            foreach ($mahasiswas as $mahasiswa) {
                $tahunMasukMahasiswa = (int) $mahasiswa->tahun_masuk;
                $maxSemesterStudiKurikulum = 8; // Default S1

                if ($mahasiswa->programStudi && $mahasiswa->programStudi->jenjang === 'D3') {
                    $maxSemesterStudiKurikulum = 6;
                } elseif (!$mahasiswa->programStudi) {
                    $this->command->warn("Mahasiswa NIM {$mahasiswa->nim} tidak memiliki program studi. Dilewati.");
                    $progressBar->advance();
                    continue;
                }
                $prodiIdMahasiswa = $mahasiswa->prodi_id;

                $semestersToGenerateFor = 0;
                if ($simulatedCurrentYear > $tahunMasukMahasiswa) {
                    $semestersToGenerateFor = ($simulatedCurrentYear - $tahunMasukMahasiswa) * 2;
                }

                foreach ($allTahunAkademik as $ta) {
                    $tahunAkademikParts = explode('/', $ta->tahun_akademik);
                    $startYearTA = (int) $tahunAkademikParts[0];

                    if ($startYearTA < $tahunMasukMahasiswa) continue;

                    $studentSemesterForThisTA = (($startYearTA - $tahunMasukMahasiswa) * 2) + ($ta->semester === 'Ganjil' ? 1 : 2);

                    if ($studentSemesterForThisTA <= 0 || 
                        $studentSemesterForThisTA > $semestersToGenerateFor ||
                        $studentSemesterForThisTA > $maxSemesterStudiKurikulum) {
                        continue;
                    }

                    $statusKrs = 'Disetujui'; 
                    $academicYearMidPoint = Carbon::createFromDate($startYearTA, ($ta->semester === 'Ganjil' ? 9 : 3), 15);
                    $pengajuanKrs = $this->faker->dateTimeBetween($academicYearMidPoint->copy()->subMonth(), $academicYearMidPoint);
                    $persetujuanKrs = Carbon::instance($pengajuanKrs)->addDays($this->faker->numberBetween(1, 14))->toDateTimeString();


                    $krsContext = new \StdClass();
                    $krsContext->unique_key = $mahasiswa->nim . '-' . $ta->id_tahunakademik;
                    $krsContext->mahasiswa_id = $mahasiswa->nim;
                    $krsContext->tahun_akademik_id = $ta->id_tahunakademik;
                    $krsContext->tanggal_pengajuan = $pengajuanKrs;
                    $krsContext->tanggal_persetujuan = $persetujuanKrs;
                    $krsContext->status = $statusKrs;
                    $krsContext->catatan = $this->faker->optional(0.1)->sentence();
                    $krsContext->created_at = $currentChunkTimestamp; 
                    $krsContext->updated_at = $currentChunkTimestamp; 
                    $krsContext->details_context = [];
                    $krsContext->total_sks = 0;

                    $matakuliahUntukSemesterIni = $this->groupedMataKuliahCache->get($prodiIdMahasiswa . '_' . $studentSemesterForThisTA, collect());

                    if ($matakuliahUntukSemesterIni->isNotEmpty()) {
                        $shuffledMataKuliah = $matakuliahUntukSemesterIni->shuffle();
                        $mkYangBerhasilDitambahkan = 0;
                        $targetJumlahMk = $this->faker->numberBetween(1, min(7, $shuffledMataKuliah->count()));

                        foreach ($shuffledMataKuliah as $mk) {
                            if ($mkYangBerhasilDitambahkan >= $targetJumlahMk) break;

                            $availableKelasCacheKey = $ta->id_tahunakademik . '-' . $mk->kode_matakuliah;
                            $availableKelases = $this->availableKelasCache[$availableKelasCacheKey] ?? collect();

                            if ($availableKelases->isNotEmpty()) {
                                $kelas = $availableKelases->random();
                                
                                $detailContext = new \StdClass();
                                $detailContext->kelas_id = $kelas->id_kelas;
                                $detailContext->created_at = $currentChunkTimestamp; 
                                $detailContext->updated_at = $currentChunkTimestamp; 
                                $detailContext->kelas_instance = $kelas; 
                                
                                $krsContext->details_context[] = $detailContext;
                                $krsContext->total_sks += $mk->sks;
                                $mkYangBerhasilDitambahkan++;

                                $komponenConfigs = $this->getKomponenConfigs();
                                foreach($komponenConfigs as $kConfig) {
                                    $upsertKey = $kelas->id_kelas . '-' . $kConfig['nama_komponen'];
                                    $komponenNilaiForChunkUpsert[$upsertKey] = [
                                        'kelas_id' => $kelas->id_kelas,
                                        'nama_komponen' => $kConfig['nama_komponen'],
                                        'bobot' => $kConfig['bobot'],
                                        'created_at' => $currentChunkTimestamp,
                                        'updated_at' => $currentChunkTimestamp,
                                    ];
                                }
                            }
                        }
                    }
                    
                    if (!empty($krsContext->details_context) && $krsContext->total_sks > 0) {
                        $krsContextCollection[] = $krsContext;
                    } else {
                        if ($matakuliahUntukSemesterIni->isNotEmpty() && empty($krsContext->details_context)) {
                            $this->command->warn("INFO: MHS {$mahasiswa->nim} TA {$ta->tahun_akademik}/{$ta->semester} (Sem {$studentSemesterForThisTA}): MK ada, tidak ada kelas aktif. KRS tidak dibuat.");
                        } else if ($matakuliahUntukSemesterIni->isEmpty()) {
                            // $this->command->line("INFO: MHS {$mahasiswa->nim} TA {$ta->tahun_akademik}/{$ta->semester} (Sem {$studentSemesterForThisTA}): Tidak ada MK di kurikulum. KRS tidak dibuat.");
                        }
                    }
                } 
                $progressBar->advance();
            } 

            if (!empty($komponenNilaiForChunkUpsert)) {
                KomponenNilai::upsert(
                    array_values($komponenNilaiForChunkUpsert),
                    ['kelas_id', 'nama_komponen'], 
                    ['bobot', 'updated_at'] 
                );
                
                $kelasIdsProcessed = collect($komponenNilaiForChunkUpsert)->pluck('kelas_id')->unique();
                $namaKomponenProcessed = collect($komponenNilaiForChunkUpsert)->pluck('nama_komponen')->unique();
                
                if ($kelasIdsProcessed->isNotEmpty() && $namaKomponenProcessed->isNotEmpty()) {
                    $actualKomponenNilai = KomponenNilai::whereIn('kelas_id', $kelasIdsProcessed)
                                                        ->whereIn('nama_komponen', $namaKomponenProcessed)
                                                        ->get();
                    foreach($actualKomponenNilai as $kn) {
                        $this->komponenNilaiCache[$kn->kelas_id . '-' . $kn->nama_komponen] = $kn;
                    }
                }
            }


            if (empty($krsContextCollection)) return;

            $krsInsertBatch = [];
            foreach ($krsContextCollection as $krsCtx) {
                $krsInsertBatch[] = [
                    'mahasiswa_id' => $krsCtx->mahasiswa_id,
                    'tahun_akademik_id' => $krsCtx->tahun_akademik_id,
                    'tanggal_pengajuan' => $krsCtx->tanggal_pengajuan,
                    'tanggal_persetujuan' => $krsCtx->tanggal_persetujuan,
                    'status' => $krsCtx->status,
                    'catatan' => $krsCtx->catatan,
                    'total_sks' => $krsCtx->total_sks,
                    'created_at' => $krsCtx->created_at,
                    'updated_at' => $krsCtx->updated_at,
                ];
            }
            if (!empty($krsInsertBatch)) {
                Krs::insert($krsInsertBatch);
            }


            $krsIdMap = []; 
            $mahasiswaNimsInChunk = collect($krsInsertBatch)->pluck('mahasiswa_id')->unique()->all(); // Ambil dari batch yg benar-benar akan diinsert
            $tahunAkademikIdsInContext = collect($krsInsertBatch)->pluck('tahun_akademik_id')->unique()->all(); // Ambil dari batch

            if (!empty($krsInsertBatch)) {
                $timeWindowStart = $currentChunkTimestamp->copy()->subSeconds(10); // Kurangi jadi 10 detik
                $timeWindowEnd = $currentChunkTimestamp->copy()->addSeconds(10); // Kurangi jadi 10 detik

                if (!empty($mahasiswaNimsInChunk) && !empty($tahunAkademikIdsInContext)) {
                     $insertedKrsModels = Krs::whereIn('mahasiswa_id', $mahasiswaNimsInChunk)
                        ->whereIn('tahun_akademik_id', $tahunAkademikIdsInContext) 
                        ->where('created_at', '>=', $timeWindowStart) 
                        ->where('created_at', '<=', $timeWindowEnd)
                        ->select('id_krs', 'mahasiswa_id', 'tahun_akademik_id')
                        ->get();
                    foreach ($insertedKrsModels as $krsModel) {
                        $krsIdMap[$krsModel->mahasiswa_id . '-' . $krsModel->tahun_akademik_id] = $krsModel->id_krs;
                    }
                }
            }

            $krsDetailInsertBatch = [];
            $krsDetailInternalMap = []; 
            foreach ($krsContextCollection as $krsCtx) {
                $actualKrsId = $krsIdMap[$krsCtx->unique_key] ?? null;
                if ($actualKrsId && !empty($krsCtx->details_context)) { 
                    foreach ($krsCtx->details_context as $detailCtx) {
                        $krsDetailInsertBatch[] = [
                            'krs_id' => $actualKrsId,
                            'kelas_id' => $detailCtx->kelas_id,
                            'created_at' => $detailCtx->created_at,
                            'updated_at' => $detailCtx->updated_at,
                        ];
                        $krsDetailInternalMap[$actualKrsId . '-' . $detailCtx->kelas_id] = [
                            'kelas_instance' => $detailCtx->kelas_instance,
                        ];
                    }
                }
            }
            if(!empty($krsDetailInsertBatch)) {
                KrsDetail::insert($krsDetailInsertBatch);
            }
            
            if (!empty($krsDetailInsertBatch)) { 
                $krsIdsForDetailQuery = collect($krsDetailInsertBatch)->pluck('krs_id')->unique()->all();
                $kelasIdsForDetailQuery = collect($krsDetailInsertBatch)->pluck('kelas_id')->unique()->all();
                
                $timeWindowStartDetail = $currentChunkTimestamp->copy()->subSeconds(10); // Kurangi jadi 10 detik
                $timeWindowEndDetail = $currentChunkTimestamp->copy()->addSeconds(10); // Kurangi jadi 10 detik
                
                if (!empty($krsIdsForDetailQuery) && !empty($kelasIdsForDetailQuery)) {
                    $insertedKrsDetailModels = KrsDetail::whereIn('krs_id', $krsIdsForDetailQuery) 
                        ->whereIn('kelas_id', $kelasIdsForDetailQuery) 
                        ->where('created_at', '>=', $timeWindowStartDetail)
                        ->where('created_at', '<=', $timeWindowEndDetail)
                        ->select('id_krsdetail', 'krs_id', 'kelas_id')
                        ->get();
                    foreach ($insertedKrsDetailModels as $krsDetailModel) {
                        $mapKey = $krsDetailModel->krs_id . '-' . $krsDetailModel->kelas_id;
                        if(isset($krsDetailInternalMap[$mapKey])) { 
                            $krsDetailInternalMap[$mapKey]['id_krsdetail'] = $krsDetailModel->id_krsdetail;
                        }
                    }
                }
            }

            $nilaiToInsert = [];
            $nilaiAkhirToInsert = [];
            if(!empty($krsDetailInternalMap)){ 
                foreach ($krsDetailInternalMap as $contextKey => $contextData) { 
                    if (isset($contextData['id_krsdetail']) && $contextData['id_krsdetail']) {
                        $mockKrsDetail = new KrsDetail(); 
                        $mockKrsDetail->id_krsdetail = $contextData['id_krsdetail'];
                        
                        $nilaiData = $this->prepareDetailNilaiForBulk($mockKrsDetail, $contextData['kelas_instance'], $currentChunkTimestamp);
                        $nilaiToInsert = array_merge($nilaiToInsert, $nilaiData['nilai']);
                        if ($nilaiData['nilai_akhir'] !== null) {
                            $nilaiAkhirToInsert[] = $nilaiData['nilai_akhir'];
                        }
                    }
                }
            }

            if (!empty($nilaiToInsert)) {
                Nilai::insert($nilaiToInsert);
            }
            if (!empty($nilaiAkhirToInsert)) {
                NilaiAkhir::insert($nilaiAkhirToInsert);
            }


        }); 

        $progressBar->finish();
        $this->command->line('');
        $this->command->info('KRS and Nilai Seeding Finished.');
        DB::enableQueryLog();
    }

    private function getKomponenConfigs(): array
    {
        $komponenConfigs = [
            ['nama_komponen' => 'Tugas Harian', 'bobot' => $this->faker->numberBetween(15, 25)],
            ['nama_komponen' => 'Ujian Tengah Semester (UTS)', 'bobot' => $this->faker->numberBetween(25, 35)],
        ];
        $sisaBobot = 100 - ($komponenConfigs[0]['bobot'] + $komponenConfigs[1]['bobot']);
        $komponenConfigs[] = ['nama_komponen' => 'Ujian Akhir Semester (UAS)', 'bobot' => max(20, $sisaBobot)];

        $totalBobot = array_sum(array_column($komponenConfigs, 'bobot'));
        if ($totalBobot !== 100 && $totalBobot > 0) {
            $factor = 100 / $totalBobot;
            $currentSumBobot = 0;
            for ($i = 0; $i < count($komponenConfigs) - 1; $i++) {
                $komponenConfigs[$i]['bobot'] = round($komponenConfigs[$i]['bobot'] * $factor);
                $currentSumBobot += $komponenConfigs[$i]['bobot'];
            }
            $komponenConfigs[count($komponenConfigs)-1]['bobot'] = 100 - $currentSumBobot;
        }
        $finalBobotSum = array_sum(array_column($komponenConfigs, 'bobot'));
        if ($finalBobotSum !== 100 && count($komponenConfigs) > 0) {
            $komponenConfigs[count($komponenConfigs)-1]['bobot'] += (100 - $finalBobotSum);
        }
        return $komponenConfigs;
    }


    private function prepareDetailNilaiForBulk(KrsDetail $krsDetail, Kelas $kelas, Carbon $timestamp): array
    {
        $komponenConfigs = $this->getKomponenConfigs(); 

        $nilaiDataArray = [];
        $totalNilaiWeighted = 0;

        foreach ($komponenConfigs as $config) {
            if ($config['bobot'] <= 0) continue; 

            $komponenNilai = $this->komponenNilaiCache[$kelas->id_kelas . '-' . $config['nama_komponen']] ?? null;

            if (!$komponenNilai) {
                $this->command->error("CRITICAL: KomponenNilai tidak ada di cache untuk Kelas ID {$kelas->id_kelas}, Komponen '{$config['nama_komponen']}'. Nilai tidak dapat dibuat. Pastikan upsert KomponenNilai berhasil & cache direfresh.");
                continue; 
            }

            $nilaiAngkaKomponen = $this->faker->randomFloat(2, 45, 98);
            $nilaiDataArray[] = [
                'krs_detail_id' => $krsDetail->id_krsdetail, 
                'komponen_nilai_id' => $komponenNilai->id_komponennilai,
                'nilai_angka' => $nilaiAngkaKomponen,
                'created_at' => $timestamp, 
                'updated_at' => $timestamp, 
            ];
            $totalNilaiWeighted += ($nilaiAngkaKomponen * ($komponenNilai->bobot / 100.0));
        }

        if (empty($nilaiDataArray)) {
            return ['nilai' => [], 'nilai_akhir' => null];
        }

        $nilaiAngkaAkhir = round(max(0, min(100, $totalNilaiWeighted)), 2);
        $nilaiHuruf = 'E';
        if ($nilaiAngkaAkhir >= 80) $nilaiHuruf = 'A';
        elseif ($nilaiAngkaAkhir >= 75) $nilaiHuruf = 'AB';
        elseif ($nilaiAngkaAkhir >= 70) $nilaiHuruf = 'B';
        elseif ($nilaiAngkaAkhir >= 65) $nilaiHuruf = 'BC';
        elseif ($nilaiAngkaAkhir >= 60) $nilaiHuruf = 'C';
        elseif ($nilaiAngkaAkhir >= 50) $nilaiHuruf = 'D';

        $nilaiAkhirData = [
            'krs_detail_id' => $krsDetail->id_krsdetail, 
            'nilai_angka' => $nilaiAngkaAkhir,
            'nilai_huruf' => $nilaiHuruf,
            'created_at' => $timestamp, 
            'updated_at' => $timestamp, 
        ];


        return ['nilai' => $nilaiDataArray, 'nilai_akhir' => $nilaiAkhirData];
    }
}