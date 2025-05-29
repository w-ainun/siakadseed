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

    public function run(){

        $angkatan = $this->targetAngkatan();
        $this->command->info("Menghapus data KRS lama untuk angkatan $angkatan...");

        // Ambil semua NIM mahasiswa dari angkatan ini
        $mahasiswaNims = Mahasiswa::where('tahun_masuk', $angkatan)->pluck('nim');

        // Ambil semua ID KRS milik mahasiswa tersebut
        $krsIds = DB::table('krs')->whereIn('mahasiswa_id', $mahasiswaNims)->pluck('id_krs');

        // Hapus krs_detail terlebih dahulu karena ada foreign key ke krs
        DB::table('krs_detail')->whereIn('krs_id', $krsIds)->delete();
        DB::table('krs')->whereIn('id_krs', $krsIds)->delete();

        $this->command->info("Data KRS lama dihapus. Melanjutkan proses seeding...");
        $angkatan = $this->targetAngkatan();
        $this->command->info("Seeding 40000 KRS untuk angkatan $angkatan...");

        $tahunAkademiks = TahunAkademik::orderBy('tahun_akademik')->orderByRaw("FIELD(semester, 'Ganjil', 'Genap')")->take(8)->get();

        $kelasList = Kelas::with('mataKuliah.kurikulum')->get()->groupBy('tahun_akademik_id');
        $mahasiswas = Mahasiswa::where('tahun_masuk', $angkatan)->get()->keyBy('nim');

        if ($tahunAkademiks->isEmpty() || $kelasList->isEmpty() || $mahasiswas->isEmpty()) {
            $this->command->error('Data dasar tidak ditemukan.');
            return;
        }

        $progressBar = $this->command->getOutput()->createProgressBar(8 * 5000);
        $progressBar->setFormatDefinition('custom', ' %current%/%max% [%bar%] %percent:3s%% -- %message% (Est: %estimated:-6s% Left: %remaining:-6s%)');
        $progressBar->setFormat('custom');
        $progressBar->start();

        $krsDetailsToInsert = [];
        $krsUpdates = [];

        foreach ($tahunAkademiks as $ta) {
            $taKelas = $kelasList->get($ta->id_tahunakademik, collect())->filter(fn($kelas) => $kelas->mataKuliah && $kelas->mataKuliah->kurikulum);
            $eligibleMahasiswa = $mahasiswas->filter(fn($m) => !$this->mahasiswaHasKrs($m->nim, $ta->id_tahunakademik));

            $eligibleNims = $eligibleMahasiswa->keys()->shuffle()->take(5000);

            foreach ($eligibleNims as $nim) {
                $mahasiswa = $mahasiswas->get($nim);
                $kelasPilihan = $taKelas->filter(fn($kelas) =>
                    ($kelas->mataKuliah->kurikulum->prodi_id ?? null) === $mahasiswa->prodi_id
                )->shuffle()->take($this->faker->numberBetween(5, 8));

                if ($kelasPilihan->isEmpty()) continue;

                $krsId = DB::table('krs')->insertGetId([
                    'mahasiswa_id' => $nim,
                    'tahun_akademik_id' => $ta->id_tahunakademik,
                    'tanggal_pengajuan' => $this->faker->dateTimeBetween($ta->tanggal_mulai, Carbon::parse($ta->tanggal_mulai)->addMonth(1)),
                    'status' => 'Disetujui',
                    'total_sks' => 0,
                    'tanggal_persetujuan' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $totalSks = 0;
                foreach ($kelasPilihan as $kelas) {
                    $krsDetailsToInsert[] = [
                        'krs_id' => $krsId,
                        'kelas_id' => $kelas->id_kelas,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $totalSks += $kelas->mataKuliah->sks ?? 0;
                }
                $krsUpdates[$krsId] = $totalSks;

                if (count($krsDetailsToInsert) >= $this->batchSize) {
                    DB::table('krs_detail')->insert($krsDetailsToInsert);
                    $krsDetailsToInsert = [];
                }

                $progressBar->advance();
            }
        }

        if (!empty($krsDetailsToInsert)) {
            DB::table('krs_detail')->insert($krsDetailsToInsert);
        }

        $this->command->info("\nMengupdate total SKS...");
        $updateBar = $this->command->getOutput()->createProgressBar(count($krsUpdates));
        $updateBar->setFormat('custom');
        $updateBar->start();

        $batch = [];
        foreach ($krsUpdates as $krsId => $sks) {
            $batch[] = ['id_krs' => $krsId, 'total_sks' => $sks];

            if (count($batch) >= $this->batchSize) {
                DB::table('krs')->upsert($batch, ['id_krs'], ['total_sks']);
                $updateBar->advance(count($batch));
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('krs')->upsert($batch, ['id_krs'], ['total_sks']);
            $updateBar->advance(count($batch));
        }

        $updateBar->finish();
        $this->command->info("\nSelesai seeding.");
    }   
    protected function mahasiswaHasKrs($nim, $tahunAkademikId)
    {
        return DB::table('krs')
            ->where('mahasiswa_id', $nim)
            ->where('tahun_akademik_id', $tahunAkademikId)
            ->exists();
    }
}
