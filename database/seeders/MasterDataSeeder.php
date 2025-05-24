<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Fakultas;
use App\Models\ProgramStudi;
use App\Models\Dosen;
use App\Models\TahunAkademik;
use App\Models\Kurikulum;
use App\Models\MataKuliah;
use App\Models\Kelas;
use Faker\Factory as FakerFactory;
use Illuminate\Support\Collection;

class MasterDataSeeder extends Seeder
{
    protected $faker;

    protected $indonesianCities = [
        'Jakarta', 'Surabaya', 'Bandung', 'Medan', 'Semarang', 'Palembang', 'Makassar',
        'Batam', 'Pekanbaru', 'Bogor', 'Padang', 'Malang', 'Bandar Lampung', 'Denpasar',
        'Samarinda', 'Tasikmalaya', 'Serang', 'Banjarmasin', 'Pontianak', 'Cimahi',
        'Balikpapan', 'Jambi', 'Surakarta', 'Mataram', 'Manado', 'Yogyakarta', 'Cilegon',
        'Kupang', 'Palu', 'Ambon', 'Tarakan', 'Sukabumi', 'Cirebon', 'Pekalongan', 'Kediri',
        'Pematangsiantar', 'Tegal', 'Sorong', 'Binjai', 'Dumai', 'Palangka Raya', 'Singkawang',
        'Probolinggo', 'Padang Sidempuan', 'Bitung', 'Banjarbaru', 'Lubuklinggau', 'Ternate',
        'Prabumulih', 'Tanjungpinang', 'Gorontalo', 'Bau-Bau', 'Madiun', 'Salatiga', 'Pangkalpinang',
        'Lhokseumawe', 'Bima', 'Mojokerto', 'Magelang', 'Kotamobagu', 'Parepare', 'Blitar',
        'Gunungsitoli', 'Tidore Kepulauan', 'Pagar Alam', 'Payakumbuh', 'Langsa', 'Bukittinggi',
        'Pasuruan', 'Tanjungbalai', 'Metro', 'Bontang', 'Baubau', 'Kendari', 'Sibolga', 'Solok',
        'Tomohon', 'Subulussalam', 'Sungai Penuh'
    ];

    protected $mkBaseNamesByFacultyType = [
        'Teknologi' => ['Dasar Pemrograman', 'Kalkulus', 'Fisika Dasar Teknik', 'Algoritma & Struktur Data', 'Basis Data', 'Jaringan Komputer', 'Sistem Operasi', 'Rekayasa Perangkat Lunak', 'Kecerdasan Buatan', 'Keamanan Informasi', 'Matematika Diskrit', 'Logika Informatika', 'Pembelajaran Mesin', 'Pengolahan Citra Digital', 'Sistem Tertanam'],
        'Ekonomi Bisnis' => ['Pengantar Ekonomi Mikro', 'Pengantar Ekonomi Makro', 'Matematika Ekonomi', 'Statistika Ekonomi', 'Akuntansi Dasar', 'Manajemen Pemasaran', 'Manajemen Keuangan', 'Manajemen SDM', 'Ekonomi Digital', 'Bisnis Internasional', 'Ekonomi Pembangunan', 'Manajemen Operasi', 'Perilaku Konsumen', 'Audit', 'Perpajakan'],
        'Komputer Desain' => ['Logika Informatika', 'Desain Grafis Dasar', 'Pemrograman Web', 'Interaksi Manusia & Komputer', 'Multimedia', 'Animasi Digital', 'Tipografi', 'Ilustrasi', 'UI/UX Desain', 'Mobile Application Design', 'Realitas Virtual', 'Desain Game', 'Videografi', 'Fotografi Digital', 'Motion Graphic'],
        'Sosial Humaniora' => ['Pengantar Sosiologi', 'Pengantar Ilmu Politik', 'Teori Komunikasi', 'Metodologi Penelitian Sosial', 'Filsafat Ilmu', 'Psikologi Umum', 'Hukum Tata Negara', 'Hubungan Internasional', 'Antropologi Budaya', 'Bahasa Indonesia Akademik', 'Sejarah Pemikiran Modern', 'Kajian Media', 'Etika Sosial', 'Kriminologi', 'Kebijakan Publik'],
        'Kesehatan' => ['Anatomi Dasar', 'Fisiologi Manusia', 'Biokimia Kedokteran', 'Farmakologi Dasar', 'Ilmu Kesehatan Masyarakat', 'Epidemiologi', 'Gizi Kesehatan', 'Mikrobiologi & Parasitologi', 'Patologi Umum', 'Etika Profesi Kesehatan', 'Genetika Kedokteran', 'Imunologi', 'Kesehatan Reproduksi', 'Manajemen Bencana Kesehatan', 'Toksikologi']
    ];
    
    // Asumsi $electiveMkBaseNames didefinisikan sebagai properti kelas jika digunakan secara ekstensif
    // protected $electiveMkBaseNames = [ /* ... */ ];

    protected $generalMkBaseNames = ['Pendidikan Pancasila', 'Pendidikan Kewarganegaraan', 'Bahasa Inggris Akademik', 'Metodologi Riset Ilmiah', 'Technopreneurship Lanjut', 'Kuliah Kerja Nyata Tematik', 'Skripsi/Tugas Akhir I', 'Skripsi/Tugas Akhir II', 'Agama dan Etika', 'Filsafat Ilmu Pengetahuan', 'Statistika Terapan', 'Keterampilan Presentasi Ilmiah'];


    public function __construct()
    {
        $this->faker = FakerFactory::create('id_ID');
    }

    public function run()
    {
        $this->command->info('Seeding Master Data (Fakultas, Prodi, Dosen, Tahun Akademik, Kurikulum, Mata Kuliah, Kelas)...');

        $fakultasTypeMap = [];

        // 1. Fakultas (Sama seperti sebelumnya)
        $fakultasInputData = [
            ['nama_fakultas' => 'Fakultas Teknologi Industri', 'type' => 'Teknologi'],
            ['nama_fakultas' => 'Fakultas Ekonomi dan Bisnis Digital', 'type' => 'Ekonomi Bisnis'],
            ['nama_fakultas' => 'Fakultas Ilmu Komputer dan Desain', 'type' => 'Komputer Desain'],
            ['nama_fakultas' => 'Fakultas Ilmu Sosial dan Humaniora', 'type' => 'Sosial Humaniora'],
            ['nama_fakultas' => 'Fakultas Kedokteran dan Ilmu Kesehatan', 'type' => 'Kesehatan']
        ];
        $fakultasCollection = collect();
        foreach ($fakultasInputData as $fak_data) {
            $fakultas = Fakultas::factory()->create(['nama_fakultas' => $fak_data['nama_fakultas']]);
            $fakultasTypeMap[$fakultas->id_fakultas] = $fak_data['type'];
            $fakultasCollection->push($fakultas);
        }
        $this->command->info($fakultasCollection->count() . ' Fakultas created.');

        // 2. Program Studi (Sama seperti sebelumnya)
        $prodiData = [
            ['nama_prodi' => 'Teknik Informatika', 'jenjang' => 'S1', 'fakultas_id_index' => 0],
            ['nama_prodi' => 'Sistem Informasi', 'jenjang' => 'S1', 'fakultas_id_index' => 0],
            ['nama_prodi' => 'Teknik Industri', 'jenjang' => 'S1', 'fakultas_id_index' => 0],
            ['nama_prodi' => 'Manajemen', 'jenjang' => 'S1', 'fakultas_id_index' => 1],
            ['nama_prodi' => 'Akuntansi', 'jenjang' => 'S1', 'fakultas_id_index' => 1],
            ['nama_prodi' => 'Bisnis Digital', 'jenjang' => 'S1', 'fakultas_id_index' => 1],
            ['nama_prodi' => 'Desain Komunikasi Visual', 'jenjang' => 'S1', 'fakultas_id_index' => 2],
            ['nama_prodi' => 'Desain Produk', 'jenjang' => 'S1', 'fakultas_id_index' => 2],
            ['nama_prodi' => 'Ilmu Komunikasi', 'jenjang' => 'S1', 'fakultas_id_index' => 3],
            ['nama_prodi' => 'Ilmu Hukum', 'jenjang' => 'S1', 'fakultas_id_index' => 3],
            ['nama_prodi' => 'Psikologi', 'jenjang' => 'S1', 'fakultas_id_index' => 3],
            ['nama_prodi' => 'Pendidikan Dokter', 'jenjang' => 'S1', 'fakultas_id_index' => 4],
            ['nama_prodi' => 'Farmasi', 'jenjang' => 'S1', 'fakultas_id_index' => 4],
        ];
        $prodiCollection = collect();
        foreach ($prodiData as $data) {
            $fakultas = $fakultasCollection->get($data['fakultas_id_index']);
            if ($fakultas) {
                $prodi = ProgramStudi::factory()->create([
                    'nama_prodi' => $data['nama_prodi'],
                    'jenjang' => $data['jenjang'],
                    'fakultas_id' => $fakultas->id_fakultas,
                ]);
                $prodiCollection->push($prodi);
            }
        }
        $this->command->info($prodiCollection->count() . ' Program Studi created.');

        // 3. Dosen (Sama seperti sebelumnya)
        $dosenCollection = collect();
        if ($prodiCollection->isNotEmpty()) {
            foreach ($prodiCollection as $prodi) {
                //$this->command->info("Creating 50 Dosen for Prodi: {$prodi->nama_prodi} (ID: {$prodi->id_prodi})"); // Dikurangi agar tidak terlalu verbose
                for ($i = 0; $i < 50; $i++) {
                    try {
                        $nomorTelepon = '08' . $this->faker->numerify($this->faker->randomElement(['##########', '###########']));
                        $dosenCollection->push(Dosen::factory()->create([
                            'prodi_id' => $prodi->id_prodi,
                            'tempat_lahir' => $this->faker->randomElement($this->indonesianCities),
                            'nama_dosen' => $this->faker->name(),
                            'alamat' => $this->faker->address,
                            'no_telepon' => $nomorTelepon,
                        ]));
                    } catch (\Illuminate\Database\QueryException $e) {
                        if (str_contains($e->getMessage(), 'Duplicate entry')) {
                            $this->command->warn("Skipped Dosen due to duplicate NIDN for Prodi ID: {$prodi->id_prodi}. Attempt: " . ($i+1));
                        } else { throw $e; }
                    }
                }
                $this->faker->unique(true); 
            }
        } else {
            $this->command->warn('No Program Studi found. Dosen seeding skipped.');
        }
        $this->command->info("Total Dosen created: " . $dosenCollection->count());


        // Assign Kaprodi (Sama seperti sebelumnya)
        foreach ($prodiCollection as $prodi) {
            $kaprodiDosen = null;
            $possibleKaprodi = $dosenCollection->where('prodi_id', $prodi->id_prodi)->where('status', 'Aktif');
            if ($possibleKaprodi->isNotEmpty()) $kaprodiDosen = $possibleKaprodi->random();
            
            if (!$kaprodiDosen) {
                $activeDosen = $dosenCollection->where('status', 'Aktif');
                if ($activeDosen->isNotEmpty()) $kaprodiDosen = $activeDosen->random();
            }
            if (!$kaprodiDosen && $dosenCollection->isNotEmpty()) {
                 $anyDosenInProdi = $dosenCollection->where('prodi_id', $prodi->id_prodi);
                 if($anyDosenInProdi->isNotEmpty()){ $kaprodiDosen = $anyDosenInProdi->random(); }
                 else { $kaprodiDosen = $dosenCollection->random(); }
            } else if (!$kaprodiDosen && $dosenCollection->isEmpty()) { 
                 $this->command->warn("No Dosen available for Kaprodi {$prodi->nama_prodi}. Creating placeholder.");
                 $nomorTeleponKaprodiPlaceholder = '08' . $this->faker->numerify($this->faker->randomElement(['##########', '###########']));
                 $kaprodiDosen = Dosen::factory()->create([
                     'prodi_id' => $prodi->id_prodi,
                     'tempat_lahir' => $this->faker->randomElement($this->indonesianCities),
                     'nama_dosen' => $this->faker->name() . ' (Kaprodi Placeholder)',
                     'status' => 'Aktif',
                     'alamat' => $this->faker->address,
                     'no_telepon' => $nomorTeleponKaprodiPlaceholder,
                 ]);
                 $dosenCollection->push($kaprodiDosen);
            }
            if ($kaprodiDosen) ProgramStudi::where('id_prodi', $prodi->id_prodi)->update(['kaprodi_id' => $kaprodiDosen->id_dosen]);
            else $this->command->warn("Could not assign Kaprodi for {$prodi->nama_prodi}.");
        }
        $this->command->info('Kaprodi assignment process completed.');

        // 4. Tahun Akademik (Sama seperti sebelumnya)
        $tahunAkademikCollection = collect();
        for ($year = 2020; $year <= 2025; $year++) {
            $isCurrentYearForGanjil = ($year == now()->year && now()->month >= 9) || ($year == now()->year - 1 && now()->month <=1 ); // Adjusted for typical Ganjil
            $isCurrentYearForGenap = ($year == now()->year -1 && now()->month >=2 && now()->month <=7); // Adjusted for typical Genap

            // Set default active TA to 2024/2025 Ganjil for deterministic seeding if current date is outside typical academic year
            if (now()->year == 2025 && now()->month > 7 && $year == 2024 && !$tahunAkademikCollection->where('is_active', true)->first() ) { // Example: if current date is Aug 2025, make 2024 Ganjil active
                 $isCurrentYearForGanjil = true;
            }


            $tahunAkademikCollection->push(TahunAkademik::factory()->create(['tahun_akademik' => $year . '/' . ($year + 1), 'semester' => 'Ganjil', 'tanggal_mulai' => $year . '-09-01', 'tanggal_selesai' => ($year + 1) . '-01-31', 'is_active' => $isCurrentYearForGanjil, ]));
            $genapStartDate = ($year + 1) . '-02-01'; $genapEndDate = ($year + 1) . '-07-31';
            // $isGenapActive = ($isCurrentYearForGenap && now()->year == ($year + 1) && now()->between($genapStartDate, $genapEndDate)); // Original logic
            $isGenapActive = (now()->year == ($year + 1) && now()->isBetween($genapStartDate, $genapEndDate));
            $tahunAkademikCollection->push(TahunAkademik::factory()->create(['tahun_akademik' => $year . '/' . ($year + 1), 'semester' => 'Genap', 'tanggal_mulai' => $genapStartDate, 'tanggal_selesai' => $genapEndDate, 'is_active' => $isGenapActive, ]));
        }
        if ($tahunAkademikCollection->where('is_active', true)->isEmpty() && $tahunAkademikCollection->isNotEmpty()) {
            // Fallback: activate the TA for 2024/2025 Ganjil if no other is active
            $fallbackTA = $tahunAkademikCollection->firstWhere('tahun_akademik', '2024/2025', 'semester', 'Ganjil');
            if($fallbackTA) {
                $fallbackTA->is_active = true; $fallbackTA->save();
                $this->command->info("Activating fallback Tahun Akademik: {$fallbackTA->tahun_akademik} {$fallbackTA->semester}");
            } else {
                 $latestTA = $tahunAkademikCollection->sortByDesc('tanggal_mulai')->first();
                 if($latestTA) { $latestTA->is_active = true; $latestTA->save(); $this->command->info("Activating latest Tahun Akademik: {$latestTA->tahun_akademik} {$latestTA->semester}"); }
            }
        }
        $this->command->info($tahunAkademikCollection->count() . ' Tahun Akademik records created.');

        // 5. Kurikulum (Sama seperti sebelumnya)
        $allKurikulumCollection = collect();
        foreach ($prodiCollection as $prodi) {
            $allKurikulumCollection->push(Kurikulum::factory()->create(['prodi_id' => $prodi->id_prodi, 'tahun_berlaku' => $this->faker->numberBetween(2016, 2019), 'is_active' => false, 'nama_kurikulum' => 'Kurikulum ' . $this->faker->numberBetween(2016, 2019) . ' ' . $prodi->nama_prodi, 'kode_kurikulum' => 'KL-' . $prodi->id_prodi . '-' . $this->faker->unique()->numberBetween(100, 999), ]));
            $tahunBerlakuAktif = $this->faker->numberBetween(2020, 2023);
            $allKurikulumCollection->push(Kurikulum::factory()->create(['prodi_id' => $prodi->id_prodi, 'tahun_berlaku' => $tahunBerlakuAktif, 'is_active' => true, 'nama_kurikulum' => 'Kurikulum ' . $tahunBerlakuAktif . ' ' . $prodi->nama_prodi, 'kode_kurikulum' => 'KA-' . $prodi->id_prodi . '-' . $this->faker->unique()->numberBetween(100, 999), ]));
            $this->faker->unique(true); 
        }
        $this->command->info($allKurikulumCollection->where('is_active', true)->count() . ' Kurikulum (active) created.');
        $this->command->info($allKurikulumCollection->where('is_active', false)->count() . ' Kurikulum (inactive) created.');
        
        // 6. Mata Kuliah (Sama seperti sebelumnya)
        $allMataKuliahCollection = collect();
        $totalMkCreatedOverall = 0;
        $activeKurikulums = $allKurikulumCollection->where('is_active', true);

        foreach ($activeKurikulums as $kurikulum) {
            $prodiTerkait = $prodiCollection->firstWhere('id_prodi', $kurikulum->prodi_id);
            $facultyType = $prodiTerkait ? ($fakultasTypeMap[$prodiTerkait->fakultas_id] ?? null) : null;
            
            $mkWajibBaseNames = $this->mkBaseNamesByFacultyType[$facultyType] ?? [];
            
            // Original line for $mkPilihanBaseNames, assuming $this->electiveMkBaseNames is defined or handled by null coalescing
            $mkPilihanBaseNames = $this->electiveMkBaseNames[$facultyType] ?? ($this->mkBaseNamesByFacultyType[$facultyType] ?? []);

            $mkGeneralBaseNames = $this->generalMkBaseNames;

            $allPossibleBaseNames = array_unique(array_merge($mkWajibBaseNames, $mkPilihanBaseNames, $mkGeneralBaseNames));
            if (empty($allPossibleBaseNames)) { $allPossibleBaseNames = ['Studi Lanjut Umum', 'Proyek Terpadu Umum', 'Topik Khusus Umum']; }

            $usedMkNamesThisKurikulum = []; 
            $totalSksWajibProdi = 0;
            $totalMkPilihanProdi = 0; 
            $mkCreatedForThisKurikulum = 0;

            for ($semester = 1; $semester <= 8; $semester++) {
                $sksWajibSemesterIni = 0;
                $jumlahMkSemesterWajib = $this->faker->numberBetween(4, 5);
                
                for ($j = 0; $j < $jumlahMkSemesterWajib; $j++) {
                    if ($sksWajibSemesterIni >= 15 && $j > 2) break; 

                    $baseNamePool = !empty($mkWajibBaseNames) ? $mkWajibBaseNames : $allPossibleBaseNames;
                    if(empty($baseNamePool)) $baseNamePool = ['MK Wajib Cadangan'];
                    $baseName = $this->faker->randomElement($baseNamePool);
                    
                    $finalMkName = $baseName; $suffixCounter = 1;
                    while(in_array($finalMkName, $usedMkNamesThisKurikulum) && $suffixCounter < 50) {
                        $finalMkName = $baseName . ' ' . $this->faker->randomElement([romanNumerals($suffixCounter + 1), 'Dasar', 'Lanjutan', 'Terapan']);
                        $finalMkName = rtrim($finalMkName); $suffixCounter++;
                    }
                    if (in_array($finalMkName, $usedMkNamesThisKurikulum)) { $finalMkName = $baseName . ' (' . $this->faker->unique()->lexify('Wajib-???') . 'S'.$semester.')'; $this->faker->unique(true); }
                    $usedMkNamesThisKurikulum[] = $finalMkName;

                    $sksMk = $this->faker->randomElement([2, 3, 4]);
                    $createdMk = MataKuliah::factory()->create([
                        'kurikulum_id' => $kurikulum->id_kurikulum,
                        'nama_mk' => $finalMkName,
                        'sks' => $sksMk,
                        'semester' => $semester,
                        'jenis' => 'Wajib',
                    ]);
                    $allMataKuliahCollection->push($createdMk);
                    $sksWajibSemesterIni += $sksMk; $totalSksWajibProdi += $sksMk; $mkCreatedForThisKurikulum++; $totalMkCreatedOverall++;
                }

                $jumlahMkSemesterPilihanOffered = $this->faker->numberBetween(3, 6);
                for ($k_mk_pilihan = 0; $k_mk_pilihan < $jumlahMkSemesterPilihanOffered; $k_mk_pilihan++) { // Renamed loop var
                    $baseNamePool = !empty($mkPilihanBaseNames) ? $mkPilihanBaseNames : $allPossibleBaseNames;
                    if(empty($baseNamePool)) $baseNamePool = ['MK Pilihan Cadangan'];
                    $baseName = $this->faker->randomElement($baseNamePool);
                    if($this->faker->boolean(30) && !empty($mkGeneralBaseNames)) $baseName = $this->faker->randomElement($mkGeneralBaseNames);

                    $finalMkName = $baseName; 
                    $suffixCounter = 1;
                    while(in_array($finalMkName, $usedMkNamesThisKurikulum) && $suffixCounter < 50) {
                        $finalMkName = $baseName . ' ' . $this->faker->randomElement(['Studi Kasus', 'Proyek Mini', 'Topik Lanjutan', 'Perspektif Baru']);
                        $finalMkName = rtrim($finalMkName); $suffixCounter++;
                    }
                    if (in_array($finalMkName, $usedMkNamesThisKurikulum)) { $finalMkName = $baseName . ' (' . $this->faker->unique()->lexify('Pilihan-???') . 'S'.$semester.')'; $this->faker->unique(true); }
                    $usedMkNamesThisKurikulum[] = $finalMkName;
                    
                    $sksMkPilihan = $this->faker->randomElement([2, 3]);
                    $createdMkPilihan = MataKuliah::factory()->create([
                        'kurikulum_id' => $kurikulum->id_kurikulum,
                        'nama_mk' => $finalMkName,
                        'sks' => $sksMkPilihan,
                        'semester' => $semester,
                        'jenis' => 'Pilihan',
                    ]);
                    $allMataKuliahCollection->push($createdMkPilihan);
                    $totalMkPilihanProdi++; $mkCreatedForThisKurikulum++; $totalMkCreatedOverall++;
                }
            }
            $prodiNameForInfo = $prodiTerkait ? $prodiTerkait->nama_prodi : 'Unknown Prodi';
            $this->command->info("Created {$mkCreatedForThisKurikulum} Mata Kuliah ({$totalSksWajibProdi} SKS Wajib, {$totalMkPilihanProdi} MK Pilihan ditawarkan) for Kurikulum ID: {$kurikulum->id_kurikulum} (Prodi: {$prodiNameForInfo})");
        }
        $this->command->info($totalMkCreatedOverall . ' total Mata Kuliah records created for active curriculums.');

        // 7. Kelas (MODIFIED for prodi-specific room names and prodi-exclusive use of same room string)
        $kelasCount = 0;
        $activeTahunAkademik = $tahunAkademikCollection->where('is_active', true)->first();

        if ($activeTahunAkademik) {
            if ($dosenCollection->isEmpty()) {
                $this->command->warn('No Dosen available. Kelas seeding will be skipped/incomplete.');
            }

            $prodiAbbreviationsCache = collect(); // Cache untuk singkatan prodi

            foreach ($allMataKuliahCollection as $mataKuliah) {
                $kurikulumMk = $allKurikulumCollection->firstWhere('id_kurikulum', $mataKuliah->kurikulum_id);
                $mkIdentifier = $mataKuliah->kode_matakuliah ?? $mataKuliah->id_mata_kuliah ?? $mataKuliah->id ?? 'Unknown MK';

                if (!$kurikulumMk) {
                    $this->command->warn("Kurikulum not found for Mata Kuliah ID: {$mkIdentifier}. Skipping Kelas.");
                    continue;
                }
                
                $prodiMkId = $kurikulumMk->prodi_id;
                $prodiMk = $prodiCollection->firstWhere('id_prodi', $prodiMkId);

                if (!$prodiMk) {
                    $this->command->warn("Prodi with ID {$prodiMkId} not found for Mata Kuliah {$mkIdentifier}. Skipping Kelas creation.");
                    continue;
                }

                // Get or create prodi abbreviation
                $prodiAbbreviation = $prodiAbbreviationsCache->get($prodiMk->id_prodi);
                if (!$prodiAbbreviation) {
                    $namaProdi = $prodiMk->nama_prodi;
                    $words = explode(' ', $namaProdi);
                    $_abbr = '';
                    if (count($words) > 1) {
                        foreach ($words as $word) {
                            if (!empty($word)) {
                                $_abbr .= strtoupper(mb_substr($word, 0, 1, 'UTF-8'));
                            }
                        }
                        $prodiAbbreviation = mb_substr($_abbr, 0, 3, 'UTF-8'); 
                    } else {
                        $prodiAbbreviation = strtoupper(mb_substr($namaProdi, 0, 3, 'UTF-8')); 
                    }

                    if (empty($prodiAbbreviation)) {
                        $prodiAbbreviation = 'P' . $prodiMk->id_prodi; 
                    }
                    $prodiAbbreviationsCache->put($prodiMk->id_prodi, $prodiAbbreviation);
                }

                $dosenPengampu = null;
                $possibleDosenProdi = $dosenCollection->where('prodi_id', $prodiMkId)->where('status', 'Aktif');
                if ($possibleDosenProdi->isNotEmpty()) {
                    $dosenPengampu = $possibleDosenProdi->random();
                }
                if (!$dosenPengampu) {
                    $possibleActiveDosen = $dosenCollection->where('status', 'Aktif');
                    if ($possibleActiveDosen->isNotEmpty()) {
                        $dosenPengampu = $possibleActiveDosen->random();
                    }
                }
                if (!$dosenPengampu && $dosenCollection->isNotEmpty()) {
                    $dosenPengampu = $dosenCollection->random();
                }
                
                if (!$dosenPengampu) {
                    $this->command->warn("No Dosen available for MK ID: {$mkIdentifier} (Prodi ID: {$prodiMkId}). Skipping Kelas creation for this MK.");
                    continue;
                }

                $jumlahKelasPerMk = $this->faker->numberBetween(1, 2);
                for ($k_kelas = 0; $k_kelas < $jumlahKelasPerMk; $k_kelas++) {
                    $jamMulaiOptions = ['07:00:00', '08:00:00', '09:30:00', '10:00:00', '13:00:00', '14:30:00', '15:00:00', '16:00:00'];
                    $jamMulai = $this->faker->randomElement($jamMulaiOptions);
                    $durasiMenit = $mataKuliah->sks * 50;
                    $jamSelesai = date('H:i:s', strtotime($jamMulai) + $durasiMenit * 60);

                    $baseRuangan = 'R.' . $this->faker->randomElement(['A', 'B', 'C', 'D', 'G', 'E', 'F', 'H', 'I', 'J']) 
                                   . $this->faker->numberBetween(101, 309); 

                    $ruanganName = $prodiAbbreviation . '-' . $baseRuangan;

                    Kelas::factory()->create([
                        'tahun_akademik_id' => $activeTahunAkademik->id_tahunakademik,
                        'mata_kuliah_id' => $mataKuliah->kode_matakuliah, // Asumsi kode_matakuliah adalah FK
                        'dosen_id' => $dosenPengampu->id_dosen,
                        'hari' => $this->faker->randomElement(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']),
                        'jam_mulai' => $jamMulai,
                        'jam_selesai' => $jamSelesai,
                        'ruangan' => $ruanganName, // Nama ruangan yang sudah dimodifikasi
                        'kapasitas' => $this->faker->randomElement([20, 25, 30, 35, 40, 45, 50, 60]),
                        'is_active' => true,
                    ]);
                    $kelasCount++;
                }
            }
            $this->command->info($kelasCount . ' Kelas records created with prodi-specific room names for active tahun akademik and mata kuliah.');
        } else {
            $this->command->warn('No active Tahun Akademik found. Kelas seeding skipped.');
        }

        $this->command->info('Master Data seeding completed successfully.');
    }
}

// Helper function
if (!function_exists('romanNumerals')) {
    function romanNumerals($number) {
        if ($number <= 0) return '';
        $map = ['M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400, 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40, 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1];
        $returnValue = '';
        while ($number > 0) {
            foreach ($map as $roman => $int) {
                if($number >= $int) { $number -= $int; $returnValue .= $roman; break; }
            }
        }
        return $returnValue;
    }
}