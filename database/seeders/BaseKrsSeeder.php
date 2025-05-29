<?php 
// database/seeders/BaseKrsSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mahasiswa;
use App\Models\TahunAkademik;
use App\Models\Kelas;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Faker\Factory as FakerFactory;
use Carbon\Carbon;

abstract class BaseKrsSeeder extends Seeder
{
    protected $faker;
    protected $batchSize = 1000;
    protected $mahasiswaChunkSize = 1000;

    abstract protected function targetAngkatan(): int;

    public function __construct()
    {
        $this->faker = FakerFactory::create('id_ID');
    }

    public function run()
    {
        $angkatan = $this->targetAngkatan();
        $this->command->info("Memulai seeding KRS untuk angkatan $angkatan...");

        $tahunAkademiks = TahunAkademik::orderBy('tahun_akademik')->orderByRaw("FIELD(semester, 'Ganjil', 'Genap')")->get();
        $kelasList = Kelas::with('mataKuliah')->get()->keyBy('id_kelas');

        if ($tahunAkademiks->isEmpty() || $kelasList->isEmpty()) {
            $this->command->error('Data dasar tidak ditemukan. Seeder dihentikan.');
            return;
        }

        $mahasiswaQuery = Mahasiswa::where('tahun_masuk', $angkatan);
        $totalMahasiswa = $mahasiswaQuery->count();

        $progressBar = $this->command->getOutput()->createProgressBar($totalMahasiswa);
        $progressBar->setFormatDefinition('custom', ' %current%/%max% [%bar%] %percent:3s%% -- %message% (Est: %estimated:-6s% Left: %remaining:-6s%)');
        $progressBar->setFormat('custom');
        $progressBar->start();

        $krsUpdates = [];
        $krsDetailsToInsert = [];

        $mahasiswaQuery->chunkById($this->mahasiswaChunkSize, function ($mahasiswas) use ($tahunAkademiks, $kelasList, &$krsUpdates, &$krsDetailsToInsert, $progressBar) {
            foreach ($mahasiswas as $mahasiswa) {
                $progressBar->setMessage("Memproses {$mahasiswa->nama} ({$mahasiswa->nim})");
                $tahunMasuk = (int) $mahasiswa->tahun_masuk;

                $relevantTahunAkademiks = $tahunAkademiks->filter(function ($ta) use ($tahunMasuk) {
                    $taStartYear = (int)explode('/', $ta->tahun_akademik)[0];
                    return $taStartYear >= $tahunMasuk;
                });

                $semesterCount = 0;
                foreach ($relevantTahunAkademiks as $ta) {
                    if ($semesterCount >= 8 || !$this->faker->boolean(80)) continue;

                    $relevantKelas = $kelasList->filter(fn($kelas) =>
                        $kelas->tahun_akademik_id == $ta->id_tahunakademik &&
                        ($kelas->mataKuliah->kurikulum->prodi_id ?? null) == $mahasiswa->prodi_id
                    );

                    if ($relevantKelas->isEmpty()) continue;

                    $selectedKelas = $relevantKelas->shuffle()->take($this->faker->numberBetween(5, 8));
                    if ($selectedKelas->isEmpty()) continue;

                    $existing = DB::table('krs')
                        ->where('mahasiswa_id', $mahasiswa->nim)
                        ->where('tahun_akademik_id', $ta->id_tahunakademik)
                        ->exists();

                    if ($existing) {
                        $progressBar->setMessage("KRS sudah ada untuk {$mahasiswa->nim} di TA {$ta->tahun_akademik}, dilewati.");
                        continue;
                    }

                    $krsId = DB::table('krs')->insertGetId([
                        'mahasiswa_id' => $mahasiswa->nim,
                        'tahun_akademik_id' => $ta->id_tahunakademik,
                        'tanggal_pengajuan' => $this->faker->dateTimeBetween($ta->tanggal_mulai, Carbon::parse($ta->tanggal_mulai)->addMonth(1))->format('Y-m-d H:i:s'),
                        'status' => 'Disetujui',
                        'total_sks' => 0,
                        'tanggal_persetujuan' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $sks = 0;
                    foreach ($selectedKelas as $kelas) {
                        $krsDetailsToInsert[] = [
                            'krs_id' => $krsId,
                            'kelas_id' => $kelas->id_kelas,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $sks += $kelas->mataKuliah->sks ?? 0;
                    }
                    $krsUpdates[$krsId] = $sks;

                    if (count($krsDetailsToInsert) >= $this->batchSize) {
                        DB::table('krs_detail')->insert($krsDetailsToInsert);
                        $krsDetailsToInsert = [];
                    }

                    $semesterCount++;
                }
                $progressBar->advance();
            }

            if (!empty($krsDetailsToInsert)) {
                DB::table('krs_detail')->insert($krsDetailsToInsert);
                $krsDetailsToInsert = [];
            }
        });

        $progressBar->finish();

        // Update total SKS secara batch
        $this->command->info("Mengupdate total SKS...");
        $updateProgress = $this->command->getOutput()->createProgressBar(count($krsUpdates));
        $updateProgress->setFormat('custom');
        $updateProgress->start();

        $batch = [];
        foreach ($krsUpdates as $krsId => $sks) {
            $batch[] = ['id_krs' => $krsId, 'total_sks' => $sks];

            if (count($batch) >= $this->batchSize) {
                DB::table('krs')->upsert($batch, ['id_krs'], ['total_sks']);
                $updateProgress->advance(count($batch));
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('krs')->upsert($batch, ['id_krs'], ['total_sks']);
            $updateProgress->advance(count($batch));
        }

        $updateProgress->finish();
        $this->command->info("\nSelesai seeding angkatan {$angkatan}.");
    }
}
