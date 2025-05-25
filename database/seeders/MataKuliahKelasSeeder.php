<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Fakultas;
use App\Models\ProgramStudi;
use App\Models\Kurikulum;
use App\Models\MataKuliah;
use App\Models\Kelas;
use App\Models\Dosen;
use App\Models\TahunAkademik;
use Faker\Factory as FakerFactory;

class MataKuliahKelasSeeder extends Seeder
{
    protected $faker;

    protected $mkBaseNamesByFacultyType = [
        'Teknologi' => ['Dasar Pemrograman', 'Kalkulus', 'Fisika Dasar Teknik', 'Algoritma & Struktur Data', 'Basis Data', 'Jaringan Komputer', 'Sistem Operasi', 'Rekayasa Perangkat Lunak', 'Kecerdasan Buatan', 'Keamanan Informasi', 'Matematika Diskrit', 'Logika Informatika', 'Pembelajaran Mesin', 'Pengolahan Citra Digital', 'Sistem Tertanam'],
        'Ekonomi Bisnis' => ['Pengantar Ekonomi Mikro', 'Pengantar Ekonomi Makro', 'Matematika Ekonomi', 'Statistika Ekonomi', 'Akuntansi Dasar', 'Manajemen Pemasaran', 'Manajemen Keuangan', 'Manajemen SDM', 'Ekonomi Digital', 'Bisnis Internasional', 'Ekonomi Pembangunan', 'Manajemen Operasi', 'Perilaku Konsumen', 'Audit', 'Perpajakan'],
        'Komputer Desain' => ['Logika Informatika', 'Desain Grafis Dasar', 'Pemrograman Web', 'Interaksi Manusia & Komputer', 'Multimedia', 'Animasi Digital', 'Tipografi', 'Ilustrasi', 'UI/UX Desain', 'Mobile Application Design', 'Realitas Virtual', 'Desain Game', 'Videografi', 'Fotografi Digital', 'Motion Graphic'],
        'Sosial Humaniora' => ['Pengantar Sosiologi', 'Pengantar Ilmu Politik', 'Teori Komunikasi', 'Metodologi Penelitian Sosial', 'Filsafat Ilmu', 'Psikologi Umum', 'Hukum Tata Negara', 'Hubungan Internasional', 'Antropologi Budaya', 'Bahasa Indonesia Akademik', 'Sejarah Pemikiran Modern', 'Kajian Media', 'Etika Sosial', 'Kriminologi', 'Kebijakan Publik'],
        'Kesehatan' => ['Anatomi Dasar', 'Fisiologi Manusia', 'Biokimia Kedokteran', 'Farmakologi Dasar', 'Ilmu Kesehatan Masyarakat', 'Epidemiologi', 'Gizi Kesehatan', 'Mikrobiologi & Parasitologi', 'Patologi Umum', 'Etika Profesi Kesehatan', 'Genetika Kedokteran', 'Imunologi', 'Kesehatan Reproduksi', 'Manajemen Bencana Kesehatan', 'Toksikologi']
    ];
    
    protected $generalMkBaseNames = ['Pendidikan Pancasila', 'Pendidikan Kewarganegaraan', 'Bahasa Inggris Akademik', 'Metodologi Riset Ilmiah', 'Technopreneurship Lanjut', 'Kuliah Kerja Nyata Tematik', 'Skripsi/Tugas Akhir I', 'Skripsi/Tugas Akhir II', 'Agama dan Etika', 'Filsafat Ilmu Pengetahuan', 'Statistika Terapan', 'Keterampilan Presentasi Ilmiah'];

    protected $fakultasTypeMapping = [
        'Fakultas Teknologi Industri' => 'Teknologi',
        'Fakultas Ekonomi dan Bisnis Digital' => 'Ekonomi Bisnis',
        'Fakultas Ilmu Komputer dan Desain' => 'Komputer Desain',
        'Fakultas Ilmu Sosial dan Humaniora' => 'Sosial Humaniora',
        'Fakultas Kedokteran dan Ilmu Kesehatan' => 'Kesehatan'
    ];

    public function __construct()
    {
        $this->faker = FakerFactory::create('id_ID');
    }

    public function run()
    {
        $this->command->info('Seeding Mata Kuliah and Kelas...');

        $activeKurikulums = Kurikulum::with('programStudi.fakultas')->where('is_active', true)->get();
        $allDosen = Dosen::all();
        $activeTahunAkademik = TahunAkademik::where('is_active', true)->first();

        if ($activeKurikulums->isEmpty()) {
            $this->command->error('No active Kurikulum found. Cannot seed Mata Kuliah.');
            return;
        }
        if ($allDosen->isEmpty()) {
            $this->command->warn('No Dosen found. Kelas might not be assigned lecturers properly or skipped.');
        }
        if (!$activeTahunAkademik) {
            $this->command->error('No active Tahun Akademik found. Cannot seed Kelas.');
            return;
        }

        $totalMkCreatedOverall = 0;

        foreach ($activeKurikulums as $kurikulum) {
            $prodiTerkait = $kurikulum->programStudi;
            $fakultasTerkait = $prodiTerkait->fakultas;
            $facultyType = $this->fakultasTypeMapping[$fakultasTerkait->nama_fakultas] ?? null;
            
            $mkWajibBaseNames = $this->mkBaseNamesByFacultyType[$facultyType] ?? [];
            $mkPilihanBaseNames = []; 
            $mkGeneralBaseNames = $this->generalMkBaseNames;

            $allPossibleBaseNames = array_unique(array_merge($mkWajibBaseNames, $mkPilihanBaseNames, $mkGeneralBaseNames));
            if (empty($allPossibleBaseNames)) { 
                $allPossibleBaseNames = ['Mata Kuliah Umum A', 'Mata Kuliah Umum B', 'Topik Khusus C']; 
            }

            $usedMkNamesThisKurikulum = []; 
            $mkCreatedForThisKurikulum = 0;

            for ($semester = 1; $semester <= 8; $semester++) {
                $sksWajibSemesterIni = 0;
                $jumlahMkSemesterWajib = $this->faker->numberBetween(4, 5); 
                
                for ($j = 0; $j < $jumlahMkSemesterWajib; $j++) {
                    if ($sksWajibSemesterIni >= 18 && $j > 1) break;

                    $baseNamePool = !empty($mkWajibBaseNames) ? $mkWajibBaseNames : $allPossibleBaseNames;
                    if(empty($baseNamePool)) $baseNamePool = ['MK Wajib Placeholder'];
                    $baseName = $this->faker->randomElement($baseNamePool);
                    
                    $finalMkName = $baseName; $suffixCounter = 1;
                    while(in_array($finalMkName, $usedMkNamesThisKurikulum) && $suffixCounter < 20) {
                        $finalMkName = $baseName . ' ' . romanNumerals($suffixCounter + 1);
                        $finalMkName = rtrim($finalMkName); $suffixCounter++;
                    }
                    if (in_array($finalMkName, $usedMkNamesThisKurikulum)) { 
                        $finalMkName = $baseName . ' (S' . $semester . '-' . $this->faker->unique()->lexify('W??') . ')'; 
                        $this->faker->unique(true); 
                    }
                    $usedMkNamesThisKurikulum[] = $finalMkName;

                    MataKuliah::create([
                        'kurikulum_id' => $kurikulum->id_kurikulum,
                        'nama_mk' => $finalMkName,
                        'sks' => $this->faker->randomElement([2, 3, 4]),
                        'semester' => $semester,
                        'jenis' => 'Wajib',
                    ]);
                    $mkCreatedForThisKurikulum++; $totalMkCreatedOverall++;
                }

                $jumlahMkSemesterPilihanOffered = $this->faker->numberBetween(3, 5);
                for ($k = 0; $k < $jumlahMkSemesterPilihanOffered; $k++) {
                    $baseNamePool = !empty($mkPilihanBaseNames) ? $mkPilihanBaseNames : $allPossibleBaseNames;
                    if(empty($baseNamePool)) $baseNamePool = ['MK Pilihan Placeholder'];

                    if($this->faker->boolean(25) && !empty($mkGeneralBaseNames)) {
                        $baseName = $this->faker->randomElement($mkGeneralBaseNames);
                    } else {
                        $baseName = $this->faker->randomElement($baseNamePool);
                    }

                    $finalMkName = $baseName . ' (Pilihan)'; 
                    $suffixCounter = 1;
                    while(in_array($finalMkName, $usedMkNamesThisKurikulum) && $suffixCounter < 10) {
                        $finalMkName = $baseName . ' (Pilihan ' . romanNumerals($suffixCounter + 1) . ')';
                        $finalMkName = rtrim($finalMkName); $suffixCounter++;
                    }
                     if (in_array($finalMkName, $usedMkNamesThisKurikulum)) { 
                        $finalMkName = $baseName . ' (S' . $semester . '-P-' . $this->faker->unique()->lexify('??') . ')';
                        $this->faker->unique(true); 
                    }
                    $usedMkNamesThisKurikulum[] = $finalMkName;
                    
                    MataKuliah::create([
                        'kurikulum_id' => $kurikulum->id_kurikulum,
                        'nama_mk' => $finalMkName,
                        'sks' => $this->faker->randomElement([2, 3]),
                        'semester' => $semester,
                        'jenis' => 'Pilihan',
                    ]);
                    $mkCreatedForThisKurikulum++; $totalMkCreatedOverall++;
                }
            }
            $this->command->info("Created {$mkCreatedForThisKurikulum} Mata Kuliah for Kurikulum: {$kurikulum->nama_kurikulum} (Prodi: {$prodiTerkait->nama_prodi})");
        }
        $this->command->info($totalMkCreatedOverall . ' total Mata Kuliah records created.');

        // 7. Kelas
        $kelasCount = 0;
        $allMataKuliah = MataKuliah::whereIn('kurikulum_id', $activeKurikulums->pluck('id_kurikulum'))->get();

        if ($allMataKuliah->isEmpty()){
            $this->command->warn('No Mata Kuliah found for active kurikulums. Kelas seeding skipped.');
        } else {
            foreach ($allMataKuliah as $mataKuliah) {
                $kurikulumMk = $activeKurikulums->firstWhere('id_kurikulum', $mataKuliah->kurikulum_id);
                if (!$kurikulumMk) continue;
                
                $prodiMk = $kurikulumMk->programStudi;
                if (!$prodiMk) continue;

                $prodiAbbreviation = $prodiMk->singkatan_prodi ?? strtoupper(substr(preg_replace('/[^A-Z]/', '', $prodiMk->nama_prodi),0,3));
                 if (empty($prodiAbbreviation)) $prodiAbbreviation = 'P'. $prodiMk->id_prodi;

                $dosenPengampu = null;
                if ($allDosen->isNotEmpty()) {
                    $possibleDosenProdi = $allDosen->where('prodi_id', $prodiMk->id_prodi)->where('status', 'Aktif');
                    if ($possibleDosenProdi->isNotEmpty()) {
                        $dosenPengampu = $possibleDosenProdi->random();
                    } elseif ($allDosen->where('status', 'Aktif')->isNotEmpty()) {
                        $dosenPengampu = $allDosen->where('status', 'Aktif')->random();
                    } else {
                        $dosenPengampu = $allDosen->random();
                    }
                }
                
                if (!$dosenPengampu) {
                    $this->command->warn("No Dosen available for MK: {$mataKuliah->nama_mk} (ID: {$mataKuliah->kode_matakuliah}). Skipping Kelas creation.");
                    continue;
                }

                $jumlahKelasPerMk = $this->faker->numberBetween(1, 2);
                for ($k = 0; $k < $jumlahKelasPerMk; $k++) {
                    $jamMulaiOptions = ['07:00:00', '08:00:00', '09:30:00', '10:00:00', '13:00:00', '14:30:00', '15:00:00', '16:00:00'];
                    $jamMulai = $this->faker->randomElement($jamMulaiOptions);
                    $durasiMenit = $mataKuliah->sks * 50;
                    $jamSelesai = date('H:i:s', strtotime($jamMulai) + ($durasiMenit * 60));

                    Kelas::create([
                        'tahun_akademik_id' => $activeTahunAkademik->id_tahunakademik,
                        'mata_kuliah_id' => $mataKuliah->kode_matakuliah, 
                        'dosen_id' => $dosenPengampu->id_dosen,
                        // 'nama_kelas' => $mataKuliah->nama_mk . ' - Kelas ' . chr(65 + $k), // BARIS INI DIHAPUS/DIKOMENTARI
                        'hari' => $this->faker->randomElement(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']),
                        'jam_mulai' => $jamMulai,
                        'jam_selesai' => $jamSelesai,
                        'ruangan' => ($prodiAbbreviation ?: 'R') . '-' . $this->faker->randomElement(['A','B','C','D','E','F','G','H']) . $this->faker->numberBetween(101, 309),
                        'kapasitas' => $this->faker->randomElement([25, 30, 35, 40, 45, 50]),
                        'is_active' => true,
                    ]);
                    $kelasCount++;
                }
            }
            $this->command->info($kelasCount . ' Kelas records created.');
        }
        $this->command->info('Mata Kuliah and Kelas seeding completed.');
    }
}

// Helper function
if (!function_exists('romanNumerals')) {
    function romanNumerals($number) {
        if ($number <= 0 || $number > 3999) return (string) $number;
        $map = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
        $returnValue = '';
        while ($number > 0) {
            foreach ($map as $roman => $int) {
                if($number >= $int) {
                    $number -= $int;
                    $returnValue .= $roman;
                    break;
                }
            }
        }
        return $returnValue;
    }
}