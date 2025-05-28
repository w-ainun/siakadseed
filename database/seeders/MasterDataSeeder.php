<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Fakultas;
use App\Models\ProgramStudi;
use App\Models\Dosen;
use App\Models\TahunAkademik;
use App\Models\Kurikulum;
use Faker\Factory as FakerFactory;
use Illuminate\Support\Collection;
use Carbon\Carbon;

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

    protected $prodiGelarMap = [];

    public function __construct()
    {
        $this->faker = FakerFactory::create('id_ID');

        $this->prodiGelarMap = [
             'Teknik Informatika' => [
                'S.Kom., M.Kom.',
                'S.Kom., M.Sc.',
                'S.Kom., M.TI.', // Magister Teknologi Informasi
                'S.Kom., M.Eng.', // Master of Engineering (sering dipakai di teknik)
                'S.Kom., M.Kom., Ph.D.',
                'S.Kom., M.Sc., Ph.D.',
                'S.Kom., M.TI., Ph.D.',
                'S.Kom., M.Eng., Ph.D.',
                'S.Kom., Dr.', // Doktor (gelar umum S3 tanpa spesialisasi di belakang)
            ],
            'Sistem Informasi' => [
                'S.Kom., M.Kom.',
                'S.Kom., M.Sc.',
                'S.Kom., M.TI.',
                'S.Kom., M.Kom., Ph.D.',
                'S.Kom., M.Sc., Ph.D.',
                'S.Kom., M.TI., Ph.D.',
                'S.Kom., Dr.',
            ],
            'Teknik Industri' => [
                'S.T., M.T.',
                'S.T., M.Sc.',
                'S.T., M.Eng.',
                'S.T., M.Psi.T.', // Magister Psikologi Terapan (jika ada konsentrasi ergonomi/faktor manusia)
                'S.T., M.T., Ph.D.',
                'S.T., M.Sc., Ph.D.',
                'S.T., M.Eng., Ph.D.',
                'S.T., Dr.',
                'Ir.', // Insinyur
                'Ir., M.T.',
                'Ir., M.Sc.',
                'Ir., Ph.D.',
                'Ir., Dr.',
            ],
            'Manajemen' => [
                'S.E., M.M.', // Magister Manajemen
                'S.E., M.Sc.',
                'S.E., M.B.A.', // Master of Business Administration
                'S.E., M.Ak.', // Magister Akuntansi (jika ada konsentrasi manajemen keuangan)
                'S.E., M.M., Ph.D.',
                'S.E., M.Sc., Ph.D.',
                'S.E., M.B.A., Ph.D.',
                'S.E., Dr.',
                'S.E., M.M., Dr.',
            ],
            'Akuntansi' => [
                'S.Ak., M.Ak.',
                'S.E., M.Ak.',
                'S.Ak., M.Sc.', // Master of Science in Accounting
                'S.E., M.Sc.',
                'S.Ak., M.Ak., Ph.D.',
                'S.E., M.Ak., Ph.D.',
                'S.Ak., M.Sc., Ph.D.',
                'S.Ak., Dr.',
                'S.E., Dr.',
                'Ak.', // Akuntan (gelar profesi)
                'Ak., M.Ak.',
                'Ak., Ph.D.',
            ],
            'Bisnis Digital' => [
                'S.E., M.M.',
                'S.E., M.Sc.',
                'S.E., M.B.A.',
                'S.E., M.Kom.', // Jika ada aspek teknologi yang kuat
                'S.E., M.M., Ph.D.',
                'S.E., M.Sc., Ph.D.',
                'S.E., M.B.A., Ph.D.',
                'S.E., Dr.',
            ],
            'Desain Komunikasi Visual' => [
                'S.Ds., M.Ds.',
                'S.Sn., M.Sn.',
                'S.Ds., M.A.', // Master of Arts
                'S.Sn., M.A.',
                'S.Ds., M.Ds., Ph.D.',
                'S.Sn., M.Sn., Ph.D.',
                'S.Ds., Dr.',
                'S.Sn., Dr.',
            ],
            'Desain Produk' => [
                'S.Ds., M.Ds.',
                'S.T., M.T.',
                'S.Ds., M.Eng.', // Master of Engineering (jika desain produk berorientasi rekayasa)
                'S.T., M.Ds.', // Jika ada konsentrasi desain
                'S.Ds., M.Ds., Ph.D.',
                'S.T., M.T., Ph.D.',
                'S.Ds., Dr.',
                'S.T., Dr.',
                'Ir.', // Insinyur (jika produk terkait rekayasa)
                'Ir., M.T.',
            ],
            'Ilmu Komunikasi' => [
                'S.I.Kom., M.I.Kom.',
                'S.Sos., M.I.Kom.',
                'S.I.Kom., M.A.',
                'S.Sos., M.A.',
                'S.I.Kom., M.Sc.', // Master of Science (jika ada aspek penelitian/kuantitatif)
                'S.I.Kom., M.I.Kom., Ph.D.',
                'S.Sos., M.I.Kom., Ph.D.',
                'S.I.Kom., M.A., Ph.D.',
                'S.Sos., M.A., Ph.D.',
                'S.I.Kom., Dr.',
                'S.Sos., Dr.',
            ],
            'Ilmu Hukum' => [
                'S.H., M.H.', // Magister Hukum
                'S.H., M.Kn.', // Magister Kenotariatan
                'S.H., M.Si.', // Magister Ilmu Sosial (jika ada konsentrasi hukum dan masyarakat)
                'S.H., LL.M.', // Master of Laws (gelar internasional)
                'S.H., M.H., Ph.D.',
                'S.H., M.Kn., Ph.D.',
                'S.H., LL.M., Ph.D.',
                'S.H., Dr.',
                'Prof. Dr. (H.C.)', // Gelar kehormatan, bisa disandingkan
            ],
            'Psikologi' => [
                'S.Psi., M.Psi., Psikolog.', // Kombinasi umum untuk psikolog klinis/profesi
                'S.Psi., M.Psi.',
                'S.Psi., M.A.',
                'S.Psi., M.Sc.', // Master of Science (jika ada aspek penelitian/eksperimental)
                'S.Psi., M.Psi., Ph.D.',
                'S.Psi., M.A., Ph.D.',
                'S.Psi., M.Sc., Ph.D.',
                'S.Psi., Dr.',
                'Dr., Psikolog.', // Jika gelar S3 dan profesi
            ],
            'Pendidikan Dokter' => [
                'S.Ked., Sp.A.',
                'S.Ked., Sp.PD.',
                'S.Ked., Sp.B.',
                'S.Ked., M.Kes.', // Magister Kesehatan
                'S.Ked., M.Biomed.', // Magister Biomedik
                'S.Ked., Ph.D.',
                'S.Ked., Dr.',
                'Sp.A., Ph.D.',
                'Sp.PD., Dr.',
                'Dokter Spesialis (berbagai bidang), M.Kes.',
                'Dokter Spesialis (berbagai bidang), Ph.D.',
                'Dokter (Dr. Med. untuk gelar di Jerman)',
                'Dr. Med., Ph.D.',
            ],
            'Farmasi' => [
                'S.Farm., Apt.', // Apoteker (profesi)
                'S.Farm., M.Farm.',
                'S.Farm., M.Sc.',
                'S.Farm., Apt., M.Farm.',
                'S.Farm., Apt., M.Sc.',
                'S.Farm., M.Farm., Ph.D.',
                'S.Farm., M.Sc., Ph.D.',
                'S.Farm., Dr.',
                'Apt., Ph.D.',
                'Apt., Dr.',
            ],
            'default' => [ // Gelar umum untuk prodi yang tidak terdaftar
                'S.Pd., M.Pd.',
                'S.Si., M.Si.',
                'S.E., M.A.',
                'S.S., M.Hum.',
                'S.Ag., M.Ag.', // Sarjana Agama, Magister Agama
                'S.T., M.Eng.',
                'S.S.T., M.Tr.', // Sarjana Sains Terapan, Magister Terapan
                'S.Pd., M.Pd., Ph.D.',
                'S.Si., M.Si., Ph.D.',
                'S.Pd., M.A., Ph.D.',
                'S.S., M.Hum., Ph.D.',
                'M.Sc.',
                'M.A.',
                'M.Eng.',
                'Ph.D.',
                'Dr.',
            ],
        ];
    }

    public function run()
    {
        $this->command->info('Seeding Master Data (Fakultas, Prodi, Dosen, Tahun Akademik, Kurikulum)...');

        // 1. Fakultas
        $fakultasInputData = [
            ['nama_fakultas' => 'Fakultas Teknologi Industri'],
            ['nama_fakultas' => 'Fakultas Ekonomi dan Bisnis Digital'],
            ['nama_fakultas' => 'Fakultas Ilmu Komputer dan Desain'],
            ['nama_fakultas' => 'Fakultas Ilmu Sosial dan Humaniora'],
            ['nama_fakultas' => 'Fakultas Kedokteran dan Ilmu Kesehatan']
        ];
        $fakultasCollection = collect();
        foreach ($fakultasInputData as $fak_data) {
            $fakultas = Fakultas::firstOrCreate(
                ['nama_fakultas' => $fak_data['nama_fakultas']],
                Fakultas::factory()->make(['nama_fakultas' => $fak_data['nama_fakultas']])->toArray()
            );
            $fakultasCollection->push($fakultas);
        }
        $this->command->info($fakultasCollection->count() . ' Fakultas processed (firstOrCreate).');

        // 2. Program Studi
        $prodiData = [
            ['nama_prodi' => 'Teknik Informatika', 'jenjang' => 'S1', 'fakultas_nama' => 'Fakultas Teknologi Industri'],
            ['nama_prodi' => 'Sistem Informasi', 'jenjang' => 'S1', 'fakultas_nama' => 'Fakultas Teknologi Industri'],
            ['nama_prodi' => 'Teknik Industri', 'jenjang' => 'S1', 'fakultas_nama' => 'Fakultas Teknologi Industri'],
            ['nama_prodi' => 'Manajemen', 'jenjang' => 'S1', 'fakultas_nama' => 'Fakultas Ekonomi dan Bisnis Digital'],
            ['nama_prodi' => 'Akuntansi', 'jenjang' => 'S1', 'fakultas_nama' => 'Fakultas Ekonomi dan Bisnis Digital'],
            ['nama_prodi' => 'Bisnis Digital', 'jenjang' => 'S1', 'fakultas_nama' => 'Fakultas Ekonomi dan Bisnis Digital'],
            ['nama_prodi' => 'Desain Komunikasi Visual', 'jenjang' => 'S1', 'fakultas_nama' => 'Fakultas Ilmu Komputer dan Desain'],
            ['nama_prodi' => 'Desain Produk', 'jenjang' => 'S1', 'fakultas_nama' => 'Fakultas Ilmu Komputer dan Desain'],
            ['nama_prodi' => 'Ilmu Komunikasi', 'jenjang' => 'S1', 'fakultas_nama' => 'Fakultas Ilmu Sosial dan Humaniora'],
            ['nama_prodi' => 'Ilmu Hukum', 'jenjang' => 'S1', 'fakultas_nama' => 'Fakultas Ilmu Sosial dan Humaniora'],
            ['nama_prodi' => 'Psikologi', 'jenjang' => 'S1', 'fakultas_nama' => 'Fakultas Ilmu Sosial dan Humaniora'],
            ['nama_prodi' => 'Pendidikan Dokter', 'jenjang' => 'S1', 'fakultas_nama' => 'Fakultas Kedokteran dan Ilmu Kesehatan'],
            ['nama_prodi' => 'Farmasi', 'jenjang' => 'S1', 'fakultas_nama' => 'Fakultas Kedokteran dan Ilmu Kesehatan'],
        ];
        $prodiCollection = collect();
        foreach ($prodiData as $data) {
            $fakultas = $fakultasCollection->firstWhere('nama_fakultas', $data['fakultas_nama']);
            if ($fakultas) {
                $prodi = ProgramStudi::firstOrCreate(
                    ['nama_prodi' => $data['nama_prodi'], 'fakultas_id' => $fakultas->id_fakultas],
                    ['jenjang' => $data['jenjang']]
                );
                $prodiCollection->push($prodi);
            }
        }
        $this->command->info($prodiCollection->count() . ' Program Studi processed (firstOrCreate).');

        // 3. Dosen
        $allDosen = collect();
        if ($prodiCollection->isNotEmpty()) {
            foreach ($prodiCollection as $prodi) {
                $targetDosenPerProdi = 50;
                $existingDosenCount = Dosen::where('prodi_id', $prodi->id_prodi)->count();
                $dosenToCreate = max(0, $targetDosenPerProdi - $existingDosenCount);

                $gelarOptionsForProdi = $this->prodiGelarMap[$prodi->nama_prodi] ?? $this->prodiGelarMap['default'];

                if ($dosenToCreate > 0) {
                    for ($i = 0; $i < $dosenToCreate; $i++) {
                        try {
                            Dosen::factory()->create([
                                'prodi_id' => $prodi->id_prodi,
                                'tempat_lahir' => $this->faker->randomElement($this->indonesianCities),
                                'nama_dosen' => $this->faker->name(),
                                'alamat' => $this->faker->address,
                                'no_telepon' => '08' . $this->faker->numerify($this->faker->randomElement(['##########', '###########'])),
                                'gelar_belakang' => $this->faker->randomElement($gelarOptionsForProdi),
                            ]);
                        } catch (\Illuminate\Database\QueryException $e) {
                            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                                $this->command->warn("Skipped Dosen creation due to duplicate entry for Prodi ID: {$prodi->id_prodi}.");
                            } else {
                                throw $e;
                            }
                        }
                    }
                }
            }
            $allDosen = Dosen::all();
        }
        $this->command->info($allDosen->count() . " Total Dosen available.");

        // Assign Kaprodi
        foreach ($prodiCollection as $prodi) {
            if ($prodi->kaprodi_id && Dosen::find($prodi->kaprodi_id)) {
                $this->command->info("Kaprodi for {$prodi->nama_prodi} already assigned and valid.");
                continue;
            }

            $kaprodiDosen = null;
            $possibleKaprodi = $allDosen->where('prodi_id', $prodi->id_prodi)->where('status', 'Aktif');
            if ($possibleKaprodi->isNotEmpty()) {
                $kaprodiDosen = $possibleKaprodi->random();
            }

            if (!$kaprodiDosen && $allDosen->where('status', 'Aktif')->isNotEmpty()) {
                $kaprodiDosen = $allDosen->where('status', 'Aktif')->random();
            }
            if (!$kaprodiDosen && $allDosen->isNotEmpty()) {
                $anyDosenInProdi = $allDosen->where('prodi_id', $prodi->id_prodi);
                if ($anyDosenInProdi->isNotEmpty()){
                    $kaprodiDosen = $anyDosenInProdi->random();
                } else {
                    $kaprodiDosen = $allDosen->random();
                }
            }
            
            if ($kaprodiDosen) {
                $prodi->kaprodi_id = $kaprodiDosen->id_dosen;
                $prodi->save();
            } else {
                $this->command->warn("Could not assign Kaprodi for {$prodi->nama_prodi}. No suitable Dosen found.");
            }
        }
        $this->command->info('Kaprodi assignment process completed.');

        // 4. Tahun Akademik
        $tahunAkademikProcessed = 0;
        $currentYear = now()->year;
        $currentMonth = now()->month;

        for ($year = 2020; $year <= 2025; $year++) { 
            $isGanjilCurrent = ($year == $currentYear && $currentMonth >= 9 && $currentMonth <= 1) || ($year == ($currentYear - 1) && $currentMonth >= 9 && $currentMonth <= 1); // Fixed range for Ganjil (Sept-Jan)

            TahunAkademik::firstOrCreate(
                ['tahun_akademik' => $year . '/' . ($year + 1), 'semester' => 'Ganjil'],
                [
                    'tanggal_mulai' => Carbon::create($year, 9, 1)->toDateString(),
                    'tanggal_selesai' => Carbon::create($year + 1, 1, 31)->toDateString(),
                    'is_active' => $isGanjilCurrent
                ]
            );
            $tahunAkademikProcessed++;

            $genapStartDate = Carbon::create($year + 1, 2, 1);
            $genapEndDate = Carbon::create($year + 1, 7, 31);
            $isGenapCurrent = ($year + 1 == $currentYear && $currentMonth >= 2 && $currentMonth <= 7);

            TahunAkademik::firstOrCreate(
                ['tahun_akademik' => $year . '/' . ($year + 1), 'semester' => 'Genap'],
                [
                    'tanggal_mulai' => $genapStartDate->toDateString(),
                    'tanggal_selesai' => $genapEndDate->toDateString(),
                    'is_active' => $isGenapCurrent
                ]
            );
            $tahunAkademikProcessed++;
        }
        
        if (TahunAkademik::where('is_active', true)->doesntExist()) {
            $latestTA = TahunAkademik::orderBy('tahun_akademik', 'desc')
                                    ->orderByRaw("FIELD(semester, 'Ganjil', 'Genap') DESC")
                                    ->first();
            if ($latestTA) {
                $latestTA->is_active = true;
                $latestTA->save();
                $this->command->info("Activating fallback Tahun Akademik: {$latestTA->tahun_akademik} {$latestTA->semester}");
            }
        }
        $this->command->info(TahunAkademik::count() . ' Tahun Akademik records processed (firstOrCreate).');

        // 5. Kurikulum
        $tahunKurikulumLama = 2019;
        $tahunKurikulumAktif = 2022;

        foreach (ProgramStudi::all() as $prodi) { 
            $this->faker->unique(true);

            Kurikulum::firstOrCreate(
                ['prodi_id' => $prodi->id_prodi, 'tahun_berlaku' => $tahunKurikulumLama, 'is_active' => false],
                [
                    'nama_kurikulum' => 'Kurikulum ' . $tahunKurikulumLama . ' ' . $prodi->nama_prodi,
                    'kode_kurikulum' => 'KL-' . $prodi->id_prodi . '-' . $this->faker->unique()->numberBetween(1000, 1999)
                ]
            );
            
            $this->faker->unique(true);

            Kurikulum::firstOrCreate(
                ['prodi_id' => $prodi->id_prodi, 'tahun_berlaku' => $tahunKurikulumAktif, 'is_active' => true],
                [
                    'nama_kurikulum' => 'Kurikulum ' . $tahunKurikulumAktif . ' ' . $prodi->nama_prodi,
                    'kode_kurikulum' => 'KA-' . $prodi->id_prodi . '-' . $this->faker->unique()->numberBetween(2000, 2999)
                ]
            );
        }
        $this->command->info(Kurikulum::where('is_active', true)->count() . ' Kurikulum (active) processed (firstOrCreate).');
        $this->command->info(Kurikulum::where('is_active', false)->count() . ' Kurikulum (inactive) processed (firstOrCreate).');

        $this->command->info('Master Data (Fakultas, Prodi, Dosen, TA, Kurikulum) seeding completed.');
    }
}