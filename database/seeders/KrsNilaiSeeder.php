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
use App\Models\Absensi; // If you want to seed absensi
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
        $allMataKuliah = MataKuliah::with('kurikulum.prodi')->get(); // Eager load for efficiency
        $dosenIds = Dosen::pluck('id_dosen')->toArray();

        if ($allMataKuliah->isEmpty() || empty($dosenIds) || $allTahunAkademik->isEmpty()) {
            $this->command->error("Master data (Mata Kuliah/Dosen/Tahun Akademik) is missing. Aborting KrsNilaiSeeder.");
            return;
        }

        $mahasiswaCount = Mahasiswa::count();
        $progressBar = $this->command->getOutput()->createProgressBar($mahasiswaCount);
        $progressBar->start();

        // Process mahasiswa in chunks to manage memory, even though it iterates per student.
        Mahasiswa::with('prodi')->chunkById(200, function (Collection $mahasiswas) use ($allTahunAkademik, $allMataKuliah, $dosenIds, $progressBar) {
            foreach ($mahasiswas as $mahasiswa) {
                $tahunMasukMahasiswa = (int) $mahasiswa->tahun_masuk;
                $maxSemesterStudi = 8; // Default for S1, can be adjusted for D3 etc.
                if ($mahasiswa->prodi->jenjang === 'D3') $maxSemesterStudi = 6;

                $currentSemesterSequence = 1; // Represents student's semester in their study progression

                foreach ($allTahunAkademik as $ta) {
                    // Determine if this TA is relevant for the student
                    $tahunAkademikParts = explode('/', $ta->tahun_akademik);
                    $startYearTA = (int) $tahunAkademikParts[0];

                    // Student starts in Ganjil of their 'tahun_masuk'
                    // If TA's start year is less than student's masuk year, skip
                    if ($startYearTA < $tahunMasukMahasiswa) {
                        continue;
                    }
                    // If TA start year is student's masuk year, but it's Ganjil and student's sequence > 1, or Genap and sequence > 2, etc.
                    // This logic helps map student's study semester (1,2,3..) to actual TA
                    $studentSemesterForThisTA = (($startYearTA - $tahunMasukMahasiswa) * 2) + ($ta->semester === 'Ganjil' ? 1 : 2);

                    if ($studentSemesterForThisTA <= 0 || $studentSemesterForThisTA > $maxSemesterStudi) {
                        continue; // Student not yet started or already past max typical semesters for this TA
                    }
                    
                    // Stop seeding KRS if TA is beyond "Genap 2024" (i.e., 2024/2025 Genap)
                    if ($startYearTA > 2024 || ($startYearTA == 2024 && $ta->semester == 'Genap' && $tahunAkademikParts[1] > 2025) ) {
                         // No, this condition is a bit off. If TA is 2024/2025 Genap, it's the last one.
                         // We want to seed for 2024/2025 Genap.
                    }
                    if ($startYearTA > 2024) { // if TA is for 2025/2026 or later
                        continue;
                    }


                    // Get Mata Kuliah for student's prodi and current curriculum semester
                    $targetCurriculumSemester = $studentSemesterForThisTA;
                    $matakuliahUntukSemesterIni = $allMataKuliah
                        ->where('kurikulum.prodi_id', $mahasiswa->prodi_id)
                        ->where('kurikulum.is_active', true)
                        ->where('semester', $targetCurriculumSemester);

                    if ($matakuliahUntukSemesterIni->isNotEmpty()) {
                        $this->seedKrsDanNilaiUntukSemester(
                            $mahasiswa,
                            $ta,
                            $matakuliahUntukSemesterIni->shuffle()->take($this->faker->numberBetween(5, 7)), // Take 5-7 MKs
                            $dosenIds
                        );
                    }
                }
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->command->line(''); // New line
        $this->command->info('KRS, Kelas, and Nilai Seeding Finished.');
    }

    private function seedKrsDanNilaiUntukSemester(Mahasiswa $mahasiswa, TahunAkademik $ta, Collection $selectedMks, array $dosenIds)
    {
        if ($selectedMks->isEmpty()) {
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
            if (empty($dosenIds)) continue;

            // Get or Create Kelas (cached)
            $kelasCacheKey = $ta->id_tahunakademik . '-' . $mk->kode_matakuliah;
            if (!isset($this->kelasCache[$kelasCacheKey])) {
                $jamMulai = $this->faker->randomElement(['08:00:00', '10:30:00', '13:00:00', '15:30:00']);
                $this->kelasCache[$kelasCacheKey] = Kelas::firstOrCreate(
                    [
                        'tahun_akademik_id' => $ta->id_tahunakademik,
                        'mata_kuliah_id' => $mk->kode_matakuliah,
                        // Add a class identifier if you have multiple parallel classes for same MK, e.g., 'A', 'B'
                        // For simplicity, assuming one main class offering here.
                        'hari' => $this->faker->randomElement(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']),
                        'jam_mulai' => $jamMulai,
                    ],
                    [
                        'dosen_id' => $dosenIds[array_rand($dosenIds)],
                        'jam_selesai' => Carbon::parse($jamMulai)->addHours(2)->format('H:i:s'), // Simplified
                        'ruangan' => 'R-' . $this->faker->bothify('##?'),
                        'kapasitas' => $this->faker->numberBetween(30,50),
                        'is_active' => true,
                    ]
                );
            }
            $kelas = $this->kelasCache[$kelasCacheKey];

            $krsDetail = KrsDetail::create([
                'krs_id' => $krs->id_krs,
                'kelas_id' => $kelas->id_kelas,
            ]);
            $totalSksKrs += $mk->sks;

            // Seed Nilai for this KrsDetail
            $this->seedDetailNilai($krsDetail, $kelas);
            
            // Optionally seed Absensi
            // for ($pertemuan = 1; $pertemuan <= 14; $pertemuan++) { // 14 meetings
            //     Absensi::factory()->create([
            //         'kelas_id' => $kelas->id_kelas,
            //         'mahasiswa_id' => $mahasiswa->nim,
            //         'pertemuan_ke' => $pertemuan,
            //         'tanggal' => Carbon::parse($ta->tanggal_mulai)->addWeeks($pertemuan-1)->format('Y-m-d'), // Simplified date
            //         'materi' => 'Materi Pertemuan ke-' . $pertemuan,
            //         'is_terlaksana' => true,
            //     ]);
            // }
        }
        $krs->total_sks = $totalSksKrs;
        $krs->save();
    }

    private function seedDetailNilai(KrsDetail $krsDetail, Kelas $kelas)
    {
        $komponenConfigs = [
            ['nama_komponen' => 'Tugas Harian', 'bobot' => $this->faker->numberBetween(15, 25)],
            ['nama_komponen' => 'Ujian Tengah Semester (UTS)', 'bobot' => $this->faker->numberBetween(25, 35)],
            // UAS bobot will be calculated to make total 100
        ];
        $sisaBobot = 100 - ($komponenConfigs[0]['bobot'] + $komponenConfigs[1]['bobot']);
        $komponenConfigs[] = ['nama_komponen' => 'Ujian Akhir Semester (UAS)', 'bobot' => max(20, $sisaBobot)]; // Ensure UAS has at least 20%

        // Normalize bobot if it exceeds 100 after adjustment
        $totalBobot = array_sum(array_column($komponenConfigs, 'bobot'));
        if ($totalBobot !== 100) {
            // Simple normalization, can be improved
            $factor = 100 / $totalBobot;
            foreach($komponenConfigs as &$cfg) {
                $cfg['bobot'] = round($cfg['bobot'] * $factor);
            }
            unset($cfg); // break reference
            // Recalculate last element to ensure sum is 100 due to rounding
            $currentSum = array_sum(array_map(function($c){ return $c['bobot']; }, array_slice($komponenConfigs, 0, -1)));
            $komponenConfigs[count($komponenConfigs)-1]['bobot'] = 100 - $currentSum;
        }


        $totalNilaiWeighted = 0;

        foreach ($komponenConfigs as $config) {
            $komponenCacheKey = $kelas->id_kelas . '-' . $config['nama_komponen'];
            if (!isset($this->komponenNilaiCache[$komponenCacheKey])) {
                $this->komponenNilaiCache[$komponenCacheKey] = KomponenNilai::firstOrCreate(
                    ['kelas_id' => $kelas->id_kelas, 'nama_komponen' => $config['nama_komponen']],
                    ['bobot' => $config['bobot']]
                );
            }
            $komponenNilai = $this->komponenNilaiCache[$komponenCacheKey];

            $nilaiAngkaKomponen = $this->faker->randomFloat(2, 45, 98); // Score for this component
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