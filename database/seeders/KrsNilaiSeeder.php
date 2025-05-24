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
use App\Models\Dosen;
// use App\Models\Absensi; // Uncomment if you want to seed absensi
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;

class KrsNilaiSeeder extends Seeder
{
    protected $faker;
    // Cache for performance
    protected $kelasCache = [];
    protected $komponenNilaiCache = [];

    public function __construct()
    {
        $this->faker = FakerFactory::create('id_ID');
    }

    public function run()
    {
        $this->command->info('Starting KRS, Kelas, and Nilai Seeding...');

        $allTahunAkademik = TahunAkademik::orderBy('tahun_akademik')->orderBy('semester')->get();
        // Eager load kurikulum and its programStudi relationship
        $allMataKuliah = MataKuliah::with('kurikulum.programStudi')->get();
        $dosenIds = Dosen::pluck('id_dosen')->toArray();

        if ($allMataKuliah->isEmpty() || empty($dosenIds) || $allTahunAkademik->isEmpty()) {
            $this->command->error("Master data (Mata Kuliah/Dosen/Tahun Akademik) is missing. Aborting KrsNilaiSeeder.");
            return;
        }

        $mahasiswaCount = Mahasiswa::count();
        $progressBar = $this->command->getOutput()->createProgressBar($mahasiswaCount);
        $progressBar->start();

        // Process mahasiswa in chunks to manage memory
        // Eager load the programStudi relationship for Mahasiswa
        Mahasiswa::with('programStudi')->chunkById(200, function (Collection $mahasiswas) use ($allTahunAkademik, $allMataKuliah, $dosenIds, $progressBar) {
            foreach ($mahasiswas as $mahasiswa) {
                $tahunMasukMahasiswa = (int) $mahasiswa->tahun_masuk;
                $maxSemesterStudi = 8; // Default for S1

                // Use the correct relationship name 'programStudi' and check for null
                if ($mahasiswa->programStudi && $mahasiswa->programStudi->jenjang === 'D3') {
                    $maxSemesterStudi = 6;
                } elseif (!$mahasiswa->programStudi) {
                    $this->command->warn("Mahasiswa dengan NIM {$mahasiswa->nim} tidak memiliki Program Studi yang valid atau prodi_id tidak ditemukan. KRS tidak akan dibuat untuk mahasiswa ini pada beberapa/semua semester.");
                    // You might want to skip this student entirely for KRS generation if prodi is crucial and missing
                    // continue; // Uncomment to skip this student
                }

                // $currentSemesterSequence = 1; // This variable was defined but not used effectively for semester mapping in the loop.
                                             // $studentSemesterForThisTA calculates the student's current semester directly.

                foreach ($allTahunAkademik as $ta) {
                    $tahunAkademikParts = explode('/', $ta->tahun_akademik);
                    $startYearTA = (int) $tahunAkademikParts[0];

                    if ($startYearTA < $tahunMasukMahasiswa) {
                        continue;
                    }

                    $studentSemesterForThisTA = (($startYearTA - $tahunMasukMahasiswa) * 2) + ($ta->semester === 'Ganjil' ? 1 : 2);

                    if ($studentSemesterForThisTA <= 0 || $studentSemesterForThisTA > $maxSemesterStudi) {
                        continue;
                    }
                    
                    // If Program Studi for the student is not found, skip KRS generation for this TA
                    if (!$mahasiswa->programStudi) {
                        continue;
                    }

                    if ($startYearTA > 2024) {
                        continue;
                    }

                    $targetCurriculumSemester = $studentSemesterForThisTA;
                    $prodiIdMahasiswa = $mahasiswa->prodi_id; // Get the prodi_id from the mahasiswa table directly

                    // Filter mata kuliah from the preloaded collection
                    $matakuliahUntukSemesterIni = $allMataKuliah->filter(function ($mk) use ($prodiIdMahasiswa, $targetCurriculumSemester) {
                        return $mk->kurikulum && // Ensure kurikulum relationship is loaded
                               $mk->kurikulum->programStudi && // Ensure programStudi on kurikulum is loaded
                               $mk->kurikulum->programStudi->id_prodi == $prodiIdMahasiswa &&
                               $mk->kurikulum->is_active &&
                               $mk->semester == $targetCurriculumSemester;
                    });

                    if ($matakuliahUntukSemesterIni->isNotEmpty()) {
                        $this->seedKrsDanNilaiUntukSemester(
                            $mahasiswa,
                            $ta,
                            // Shuffle and take a random number of courses (e.g., 5 to 7)
                            $matakuliahUntukSemesterIni->shuffle()->take($this->faker->numberBetween(min(5, $matakuliahUntukSemesterIni->count()), min(7, $matakuliahUntukSemesterIni->count()))),
                            $dosenIds
                        );
                    }
                }
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->command->line('');
        $this->command->info('KRS, Kelas, and Nilai Seeding Finished.');
    }

    private function seedKrsDanNilaiUntukSemester(Mahasiswa $mahasiswa, TahunAkademik $ta, Collection $selectedMks, array $dosenIds)
    {
        if ($selectedMks->isEmpty()) {
            return;
        }

        // Check if KRS already exists for this student and TA to prevent duplicates
        $existingKrs = Krs::where('mahasiswa_id', $mahasiswa->nim)
                           ->where('tahun_akademik_id', $ta->id_tahunakademik)
                           ->first();
        if ($existingKrs) {
            // Optional: Log or inform that KRS already exists
            // $this->command->info("KRS for Mahasiswa {$mahasiswa->nim} in TA {$ta->tahun_akademik} {$ta->semester} already exists. Skipping.");
            return;
        }


        $krs = Krs::factory()->create([
            'mahasiswa_id' => $mahasiswa->nim,
            'tahun_akademik_id' => $ta->id_tahunakademik,
            'status' => $this->faker->randomElement(['Disetujui', 'Diajukan', 'Draft']),
            'total_sks' => 0, // Will be updated
        ]);

        $totalSksKrs = 0;

        foreach ($selectedMks as $mk) {
            if (empty($dosenIds)) {
                // $this->command->warn("No Dosen IDs available to assign to Kelas for MK: {$mk->kode_matakuliah}. Skipping Kelas creation.");
                continue; // Skip if no lecturers are available
            }

            $kelasCacheKey = $ta->id_tahunakademik . '-' . $mk->kode_matakuliah . '-' . ($mk->kurikulum->programStudi->id_prodi ?? 'shared'); // Make cache key more specific if classes can be shared or prodi specific
            
            if (!isset($this->kelasCache[$kelasCacheKey])) {
                $jamMulai = $this->faker->randomElement(['08:00:00', '10:30:00', '13:00:00', '15:30:00']);
                $durasiJam = ceil($mk->sks / 2); // Simple duration logic, 2 sks = 1-2 hours, 3-4 sks = 2 hours
                if ($mk->sks == 1) $durasiJam = 1;


                // Use firstOrCreate for Kelas to avoid duplicates for the same MK in the same TA
                $this->kelasCache[$kelasCacheKey] = Kelas::firstOrCreate(
                    [
                        'tahun_akademik_id' => $ta->id_tahunakademik,
                        'mata_kuliah_id' => $mk->kode_matakuliah, // Assumes mata_kuliah_id in 'kelas' table references 'kode_matakuliah' in 'mata_kuliah'
                        // Consider adding a unique class identifier if multiple parallel classes exist e.g., 'A', 'B'
                        // 'nama_kelas' => $mk->nama_mk . ' - Kelas A' // Example
                    ],
                    [
                        'dosen_id' => $dosenIds[array_rand($dosenIds)],
                        'hari' => $this->faker->randomElement(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']),
                        'jam_mulai' => $jamMulai,
                        'jam_selesai' => Carbon::parse($jamMulai)->addHours($durasiJam)->format('H:i:s'), // Adjusted duration based on SKS
                        'ruangan' => ($mk->kurikulum->programStudi->singkatan ?? 'R') . '-' . $this->faker->numberBetween(101,309), // Example prodi-based room
                        'kapasitas' => $this->faker->numberBetween(30, 50),
                        'is_active' => true,
                    ]
                );
            }
            $kelas = $this->kelasCache[$kelasCacheKey];

            // Create KrsDetail linking Krs to the Kelas
            $krsDetail = KrsDetail::create([
                'krs_id' => $krs->id_krs,
                'kelas_id' => $kelas->id_kelas,
            ]);
            $totalSksKrs += $mk->sks;

            $this->seedDetailNilai($krsDetail, $kelas);
            
            // Optionally seed Absensi
            // ... (kode absensi Anda)
        }
        $krs->total_sks = $totalSksKrs;
        $krs->save();
    }

    private function seedDetailNilai(KrsDetail $krsDetail, Kelas $kelas)
    {
        $komponenConfigs = [
            ['nama_komponen' => 'Tugas Harian', 'bobot' => $this->faker->numberBetween(15, 25)],
            ['nama_komponen' => 'Ujian Tengah Semester (UTS)', 'bobot' => $this->faker->numberBetween(25, 35)],
        ];
        $sisaBobot = 100 - ($komponenConfigs[0]['bobot'] + $komponenConfigs[1]['bobot']);
        $komponenConfigs[] = ['nama_komponen' => 'Ujian Akhir Semester (UAS)', 'bobot' => max(20, $sisaBobot)];

        $totalBobot = array_sum(array_column($komponenConfigs, 'bobot'));
        if ($totalBobot !== 100 && $totalBobot > 0) { // Avoid division by zero if totalBobot is 0
            $factor = 100 / $totalBobot;
            foreach($komponenConfigs as &$cfg) {
                $cfg['bobot'] = round($cfg['bobot'] * $factor);
            }
            unset($cfg);
            $currentSum = array_sum(array_map(function($c){ return $c['bobot']; }, array_slice($komponenConfigs, 0, -1)));
            if (count($komponenConfigs) > 0) { // Ensure there's at least one component
                 $komponenConfigs[count($komponenConfigs)-1]['bobot'] = 100 - $currentSum;
            }
        } elseif (empty($komponenConfigs)) {
            // $this->command->warn("No komponen nilai configured for Kelas ID: {$kelas->id_kelas}. Nilai tidak akan di-seed.");
            return; // No components, no grades
        }


        $totalNilaiWeighted = 0;

        foreach ($komponenConfigs as $config) {
             // Ensure bobot is not zero to prevent issues if it was rounded down or misconfigured
            if ($config['bobot'] <= 0) continue;

            $komponenCacheKey = $kelas->id_kelas . '-' . $config['nama_komponen'];
            if (!isset($this->komponenNilaiCache[$komponenCacheKey])) {
                $this->komponenNilaiCache[$komponenCacheKey] = KomponenNilai::firstOrCreate(
                    ['kelas_id' => $kelas->id_kelas, 'nama_komponen' => $config['nama_komponen']],
                    ['bobot' => $config['bobot']]
                );
            }
            $komponenNilai = $this->komponenNilaiCache[$komponenCacheKey];

            $nilaiAngkaKomponen = $this->faker->randomFloat(2, 45, 98);
            Nilai::create([
                'krs_detail_id' => $krsDetail->id_krsdetail,
                'komponen_nilai_id' => $komponenNilai->id_komponennilai,
                'nilai_angka' => $nilaiAngkaKomponen,
            ]);
            $totalNilaiWeighted += ($nilaiAngkaKomponen * ($komponenNilai->bobot / 100.0));
        }

        $nilaiAngkaAkhir = round(max(0, min(100, $totalNilaiWeighted)), 2);
        $nilaiHuruf = 'E';
        if ($nilaiAngkaAkhir >= 80) $nilaiHuruf = 'A';
        elseif ($nilaiAngkaAkhir >= 75) $nilaiHuruf = 'AB';
        elseif ($nilaiAngkaAkhir >= 70) $nilaiHuruf = 'B';
        elseif ($nilaiAngkaAkhir >= 65) $nilaiHuruf = 'BC';
        elseif ($nilaiAngkaAkhir >= 60) $nilaiHuruf = 'C';
        elseif ($nilaiAngkaAkhir >= 50) $nilaiHuruf = 'D';

        NilaiAkhir::create([
            'krs_detail_id' => $krsDetail->id_krsdetail,
            'nilai_angka' => $nilaiAngkaAkhir,
            'nilai_huruf' => $nilaiHuruf,
        ]);
    }
}