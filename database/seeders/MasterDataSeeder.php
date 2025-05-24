<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Fakultas;
use App\Models\ProgramStudi;
use App\Models\Dosen;
use App\Models\TahunAkademik;
use App\Models\Kurikulum;
use App\Models\MataKuliah;
use Faker\Factory as FakerFactory; // Import Faker

class MasterDataSeeder extends Seeder
{
    protected $faker;

    public function __construct()
    {
        $this->faker = FakerFactory::create('id_ID'); // Using Indonesian locale for Faker
    }

    public function run()
    {
        $this->command->info('Seeding Master Data (Fakultas, Prodi, Dosen, Tahun Akademik, Kurikulum, Mata Kuliah)...');

        // 1. Fakultas
        $fakultasNames = [
            'Fakultas Teknologi Industri',
            'Fakultas Ekonomi dan Bisnis Digital',
            'Fakultas Ilmu Komputer dan Desain',
            'Fakultas Ilmu Sosial dan Humaniora',
            'Fakultas Kedokteran dan Ilmu Kesehatan'
        ];
        $fakultasCollection = collect();
        foreach ($fakultasNames as $name) {
            $fakultasCollection->push(Fakultas::factory()->create(['nama_fakultas' => $name]));
        }
        $this->command->info(count($fakultasNames) . ' Fakultas created.');

        // 2. Program Studi (min 5, ensuring variety)
        $prodiData = [
            ['nama_prodi' => 'Teknik Informatika', 'jenjang' => 'S1', 'fakultas_id_index' => 0],
            ['nama_prodi' => 'Sistem Informasi', 'jenjang' => 'S1', 'fakultas_id_index' => 0],
            ['nama_prodi' => 'Manajemen Ritel', 'jenjang' => 'S1', 'fakultas_id_index' => 1],
            ['nama_prodi' => 'Akuntansi Bisnis', 'jenjang' => 'S1', 'fakultas_id_index' => 1],
            ['nama_prodi' => 'Desain Komunikasi Visual', 'jenjang' => 'S1', 'fakultas_id_index' => 2],
            ['nama_prodi' => 'Ilmu Hukum', 'jenjang' => 'S1', 'fakultas_id_index' => 3],
        ];
        $prodiCollection = collect();
        foreach ($prodiData as $data) {
            $fakultas = $fakultasCollection->get($data['fakultas_id_index']);
            if ($fakultas) {
                $prodiCollection->push(ProgramStudi::factory()->create([
                    'nama_prodi' => $data['nama_prodi'],
                    'jenjang' => $data['jenjang'],
                    'fakultas_id' => $fakultas->id_fakultas,
                ]));
            }
        }
        $this->command->info($prodiCollection->count() . ' Program Studi created.');

        // 3. Dosen (e.g., 60 Dosen)
        $dosenCollection = Dosen::factory()->count(60)->create()->each(function ($dosen) use ($prodiCollection) {
            if ($prodiCollection->isNotEmpty()) {
                $dosen->prodi_id = $prodiCollection->random()->id_prodi; // Assign to a random existing prodi
                $dosen->save();
            }
        });
        $this->command->info($dosenCollection->count() . ' Dosen created.');

        // Assign Kaprodi (Head of Study Program)
        foreach ($prodiCollection as $prodi) {
            $kaprodi = $dosenCollection->where('prodi_id', $prodi->id_prodi)->first() ?? $dosenCollection->random();
            if ($kaprodi) {
                $prodi->kaprodi_id = $kaprodi->id_dosen;
                $prodi->save();
            }
        }
        $this->command->info('Kaprodi assigned to Program Studi.');


        // 4. Tahun Akademik (2019-2024, Ganjil & Genap)
        $tahunAkademikCollection = collect();
        for ($year = 2019; $year <= 2024; $year++) {
            // Ganjil
            $tahunAkademikCollection->push(TahunAkademik::factory()->create([
                'tahun_akademik' => $year . '/' . ($year + 1),
                'semester' => 'Ganjil',
                'tanggal_mulai' => $year . '-09-01',
                'tanggal_selesai' => ($year + 1) . '-01-31',
                'is_active' => ($year == 2024), // Mark 2024 Ganjil as potentially active
            ]));
            // Genap
            $tahunAkademikCollection->push(TahunAkademik::factory()->create([
                'tahun_akademik' => $year . '/' . ($year + 1), // e.g. 2024/2025 Semester Genap
                'semester' => 'Genap',
                'tanggal_mulai' => ($year + 1) . '-02-01',
                'tanggal_selesai' => ($year + 1) . '-07-31',
                'is_active' => ($year == 2024), // Mark 2024 Genap as potentially active
            ]));
        }
        $this->command->info($tahunAkademikCollection->count() . ' Tahun Akademik records created (2019-2024, Ganjil & Genap).');

        // 5. Kurikulum (1 active per prodi, maybe an old one too)
        $kurikulumCollection = collect();
        foreach ($prodiCollection as $prodi) {
            Kurikulum::factory()->create([
                'prodi_id' => $prodi->id_prodi,
                'tahun_berlaku' => $this->faker->numberBetween(2015, 2018),
                'is_active' => false,
                'nama_kurikulum' => 'Kurikulum Lama ' . $prodi->nama_prodi,
                'kode_kurikulum' => 'KL-' . $prodi->id_prodi . '-' . $this->faker->numberBetween(2015, 2018),
            ]);
            $kurikulumCollection->push(Kurikulum::factory()->create([
                'prodi_id' => $prodi->id_prodi,
                'tahun_berlaku' => $this->faker->numberBetween(2020, 2022),
                'is_active' => true,
                'nama_kurikulum' => 'Kurikulum Aktif ' . $prodi->nama_prodi,
                'kode_kurikulum' => 'KA-' . $prodi->id_prodi . '-' . $this->faker->numberBetween(2020, 2022),
            ]));
        }
        $this->command->info($kurikulumCollection->count() . ' Kurikulum (active) created for prodi.');

        // 6. Mata Kuliah (e.g., 8-12 MK per active kurikulum semester)
        $mkBaseNames = ['Dasar Pemrograman', 'Kalkulus', 'Fisika Dasar', 'Algoritma Lanjut', 'Basis Data Terdistribusi', 'Jaringan Syaraf Tiruan', 'Sistem Pakar', 'Kecerdasan Bisnis', 'Pemrograman Game', 'Analisis Big Data', 'Manajemen Strategis TI', 'Technopreneurship', 'Bahasa Inggris Akademik', 'Statistika Industri', 'Metodologi Riset Kuantitatif', 'Etika & Hukum Siber', 'Interaksi Desain & Pengguna', 'Pemrograman Perangkat Bergerak Lanjut', 'Data Warehousing', 'Deep Learning'];
        $mataKuliahCount = 0;
        foreach ($kurikulumCollection->where('is_active', true) as $kurikulum) {
            for ($semester = 1; $semester <= 8; $semester++) { // Assuming S1 up to 8 semesters
                $mkCountThisSemester = $this->faker->numberBetween(4, 6); // Mata kuliah per semester
                for ($i = 0; $i < $mkCountThisSemester; $i++) {
                    $baseName = $this->faker->randomElement($mkBaseNames);
                    MataKuliah::factory()->create([
                        'kurikulum_id' => $kurikulum->id_kurikulum,
                        'nama_mk' => $baseName . ' Semester ' . $semester . ' (' . $this->faker->unique()->lexify('??') . ')',
                        'sks' => $this->faker->randomElement([2, 3, 4]),
                        'semester' => $semester, // Semester in which this MK is typically offered
                        'jenis' => $this->faker->randomElement(['Wajib', 'Pilihan']),
                    ]);
                    $mataKuliahCount++;
                }
            }
        }
        $this->command->info($mataKuliahCount . ' Mata Kuliah records created.');
        $this->command->info('Master Data seeding completed.');
    }
}