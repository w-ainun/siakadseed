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


    public function __construct()
    {
        $this->faker = FakerFactory::create('id_ID');
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

                if ($dosenToCreate > 0) {
                    for ($i = 0; $i < $dosenToCreate; $i++) {
                        try {
                            Dosen::factory()->create([
                                'prodi_id' => $prodi->id_prodi,
                                'tempat_lahir' => $this->faker->randomElement($this->indonesianCities),
                                'nama_dosen' => $this->faker->name(),
                                'alamat' => $this->faker->address,
                                'no_telepon' => '08' . $this->faker->numerify($this->faker->randomElement(['##########', '###########'])),
                            ]);
                        } catch (\Illuminate\Database\QueryException $e) {
                            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                                $this->command->warn("Skipped Dosen creation due to duplicate entry for Prodi ID: {$prodi->id_prodi}.");
                            } else { throw $e; }
                        }
                    }
                }
            }
            $allDosen = Dosen::all(); 
        }
        $this->command->info($allDosen->count() . " Total Dosen available.");


        // Assign Kaprodi
        foreach ($prodiCollection as $prodi) {
            if ($prodi->kaprodi_id && Dosen::find($prodi->kaprodi_id)) { // Cek jika kaprodi sudah ada dan valid
                 $this->command->info("Kaprodi for {$prodi->nama_prodi} already assigned and valid.");
                 continue;
            }

            $kaprodiDosen = null;
            $possibleKaprodi = $allDosen->where('prodi_id', $prodi->id_prodi)->where('status', 'Aktif');
            if ($possibleKaprodi->isNotEmpty()) $kaprodiDosen = $possibleKaprodi->random();
            
            if (!$kaprodiDosen && $allDosen->where('status', 'Aktif')->isNotEmpty()) {
                $kaprodiDosen = $allDosen->where('status', 'Aktif')->random();
            }
            if (!$kaprodiDosen && $allDosen->isNotEmpty()) {
                 $anyDosenInProdi = $allDosen->where('prodi_id', $prodi->id_prodi);
                 if($anyDosenInProdi->isNotEmpty()){ $kaprodiDosen = $anyDosenInProdi->random(); }
                 else { $kaprodiDosen = $allDosen->random(); } // Fallback ke dosen random jika tidak ada di prodi tsb
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
        for ($year = 2020; $year <= 2025; $year++) { 
            $isCurrentYearForGanjil = ($year == now()->year && now()->month >= 8) || ($year == now()->year - 1 && now()->month <= 2 ); // Perkiraan Ganjil
            
            TahunAkademik::firstOrCreate(
                ['tahun_akademik' => $year . '/' . ($year + 1), 'semester' => 'Ganjil'],
                [
                    'tanggal_mulai' => Carbon::create($year, 9, 1)->toDateString(),
                    'tanggal_selesai' => Carbon::create($year + 1, 1, 31)->toDateString(),
                    'is_active' => $isCurrentYearForGanjil
                ]
            );
            $tahunAkademikProcessed++;

            $genapStartDate = Carbon::create($year + 1, 2, 1);
            $genapEndDate = Carbon::create($year + 1, 7, 31);
            $isGenapActive = (now()->isBetween($genapStartDate, $genapEndDate));
            TahunAkademik::firstOrCreate(
                ['tahun_akademik' => $year . '/' . ($year + 1), 'semester' => 'Genap'],
                [
                    'tanggal_mulai' => $genapStartDate->toDateString(),
                    'tanggal_selesai' => $genapEndDate->toDateString(),
                    'is_active' => $isGenapActive
                ]
            );
            $tahunAkademikProcessed++;
        }
        if (TahunAkademik::where('is_active', true)->doesntExist()) {
            $latestTA = TahunAkademik::orderBy('tanggal_mulai', 'desc')->first();
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
            $this->faker->unique(true);
        }
        $this->command->info(Kurikulum::where('is_active', true)->count() . ' Kurikulum (active) processed (firstOrCreate).');
        $this->command->info(Kurikulum::where('is_active', false)->count() . ' Kurikulum (inactive) processed (firstOrCreate).');

        $this->command->info('Master Data (Fakultas, Prodi, Dosen, TA, Kurikulum) seeding completed.');
    }
}