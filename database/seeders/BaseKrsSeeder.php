<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mahasiswa;
use App\Models\Krs;
use App\Models\KrsDetail;
use App\Models\TahunAkademik;
use App\Models\MataKuliah;
use App\Models\Kelas;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Helper\ProgressBar;

abstract class BaseKrsSeeder extends Seeder
{
    abstract protected function targetAngkatan(): int;
    abstract protected function maxSemester(): int;

    public function run(): void
    {
        $angkatan = $this->targetAngkatan();
        $maxSemester = $this->maxSemester();

        $this->command->info("🔄 Membersihkan data KRS untuk angkatan $angkatan...");

        $mahasiswaNims = Mahasiswa::where('tahun_masuk', $angkatan)->pluck('nim');
        $krsIds = Krs::whereIn('mahasiswa_id', $mahasiswaNims)->pluck('id_krs');

        KrsDetail::whereIn('krs_id', $krsIds)->delete();
        Krs::whereIn('id_krs', $krsIds)->delete();

        $this->command->info("✅ Data lama KRS dihapus.");

        $mahasiswas = Mahasiswa::where('tahun_masuk', $angkatan)->get();
        $totalKrs = $mahasiswas->count() * $maxSemester;

        if ($totalKrs === 0) {
            $this->command->warn("⚠️ Tidak ada mahasiswa untuk angkatan $angkatan.");
            return;
        }

        $tahunAkademiks = TahunAkademik::all()
            ->keyBy(fn($ta) => $ta->tahun_akademik . '-' . $ta->semester);

        $this->command->info("🚀 Memulai seeding $totalKrs KRS untuk angkatan $angkatan...");

        $bar = $this->command->getOutput()->createProgressBar($totalKrs);
        $bar->setFormat("📦 %current%/%max% [%bar%] %percent:3s%% | ⏱️ %elapsed:6s% elapsed, ⌛ %estimated:-6s% left");
        $bar->start();

        $countInserted = 0;

        foreach ($mahasiswas as $mahasiswa) {
            for ($semesterKe = 1; $semesterKe <= $maxSemester; $semesterKe++) {
                $tahun = $mahasiswa->tahun_masuk + intval(($semesterKe - 1) / 2);
                $semesterTipe = ($semesterKe % 2 === 1) ? 'Ganjil' : 'Genap';
                $kodeTA = $tahun . '/' . ($tahun + 1) . '-' . $semesterTipe;

                $tahunAkademik = $tahunAkademiks[$kodeTA] ?? null;
                if (!$tahunAkademik) {
                    $bar->advance();
                    continue;
                }

                DB::beginTransaction();

                try {
                    $krs = Krs::create([
                        'mahasiswa_id' => $mahasiswa->nim,
                        'tahun_akademik_id' => $tahunAkademik->id_tahunakademik,
                        'status' => 'Disetujui',
                        'tanggal_persetujuan' => now(),
                        'total_sks' => 0,
                        'temp_seed_uuid' => Str::uuid()
                    ]);

                    $mataKuliahs = MataKuliah::where('kurikulum_id', function ($query) use ($mahasiswa) {
                        $query->select('id_kurikulum')
                            ->from('kurikulum')
                            ->where('prodi_id', $mahasiswa->prodi_id)
                            ->where('is_active', true)
                            ->limit(1);
                    })->where('semester', $semesterKe)->get();

                    $totalSks = 0;

                    foreach ($mataKuliahs as $mataKuliah) {
                        $kelas = Kelas::where('mata_kuliah_id', $mataKuliah->kode_matakuliah)
                            ->where('tahun_akademik_id', $tahunAkademik->id_tahunakademik)
                            ->where('is_active', true)
                            ->inRandomOrder()
                            ->first();

                        if ($kelas) {
                            KrsDetail::create([
                                'krs_id' => $krs->id_krs,
                                'kelas_id' => $kelas->id_kelas,
                                'temp_seed_uuid' => Str::uuid()
                            ]);

                            $totalSks += $mataKuliah->sks;
                        }
                    }

                    $krs->update(['total_sks' => $totalSks]);

                    DB::commit();
                    $countInserted++;
                } catch (\Throwable $e) {
                    DB::rollBack();
                    report($e);
                    $this->command->error("❌ Error buat KRS mahasiswa {$mahasiswa->nim} semester $semesterKe: " . $e->getMessage());
                }

                $bar->advance();

                if ($countInserted > 0 && $countInserted % 100 === 0) {
                    $this->command->info("📝 Telah berhasil membuat $countInserted KRS.");
                }
            }
        }

        $bar->finish();
        $this->command->newLine(2);
        $this->command->info("✅ Selesai seeding $countInserted KRS untuk angkatan $angkatan.");
        $this->command->info("📊 Total di database: " . Krs::whereIn('mahasiswa_id', $mahasiswaNims)->count() . " KRS");
    }
}
