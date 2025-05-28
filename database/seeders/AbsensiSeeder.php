<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KrsDetail;
use App\Models\Absensi;
use App\Models\Kelas; // Make sure to import Kelas model
use Faker\Factory as FakerFactory;
use Carbon\Carbon;

class AbsensiSeeder extends Seeder
{
    protected $faker;

    public function __construct()
    {
        $this->faker = FakerFactory::create('id_ID');
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting Absensi Seeding...');

        // Retrieve KrsDetail records that have corresponding Krs with 'Disetujui' status
        $krsDetails = KrsDetail::whereHas('krs', function ($query) {
            $query->where('status', 'Disetujui');
        })->with('krs', 'kelas')->get();

        if ($krsDetails->isEmpty()) {
            $this->command->warn('No approved KRS Details found. Skipping Absensi seeding.');
            return;
        }

        $totalAbsensiRecords = 0;
        foreach ($krsDetails as $krsDetail) {
            $kelas = $krsDetail->kelas;
            $mahasiswaNim = $krsDetail->krs->mahasiswa_id;

            if (!$kelas) {
                $this->command->warn("Kelas with ID {$krsDetail->kelas_id} not found for KrsDetail ID {$krsDetail->id_krsdetail}. Skipping absensi.");
                continue;
            }

            // Determine start and end dates for the class based on Tahun Akademik
            $tahunAkademik = $kelas->tahunAkademik;
            if (!$tahunAkademik) {
                 $this->command->warn("Tahun Akademik not found for Kelas ID {$kelas->id_kelas}. Skipping absensi.");
                 continue;
            }

            $startDate = Carbon::parse($tahunAkademik->tanggal_mulai);
            $endDate = Carbon::parse($tahunAkademik->tanggal_selesai);

            $pertemuanCount = $this->faker->numberBetween(14, 16); // Simulate 14-16 meetings per class
            $currentDate = $startDate;
            $meetingsScheduled = 0;

            for ($i = 1; $i <= $pertemuanCount; $i++) {
                // Find next occurrence of the class's day
                $dayOfWeek = $kelas->hari; // e.g., 'Senin'
                $carbonDayMap = [
                    'Minggu' => Carbon::SUNDAY,
                    'Senin' => Carbon::MONDAY,
                    'Selasa' => Carbon::TUESDAY,
                    'Rabu' => Carbon::WEDNESDAY,
                    'Kamis' => Carbon::THURSDAY,
                    'Jumat' => Carbon::FRIDAY,
                    'Sabtu' => Carbon::SATURDAY,
                ];

                if (!isset($carbonDayMap[$dayOfWeek])) {
                    $this->command->warn("Invalid day: {$dayOfWeek} for Kelas ID {$kelas->id_kelas}. Skipping absensi for this class.");
                    break;
                }

                while ($currentDate->dayOfWeek !== $carbonDayMap[$dayOfWeek]) {
                    $currentDate->addDay();
                }

                // If the calculated date exceeds the semester end date, stop scheduling
                if ($currentDate->greaterThan($endDate)) {
                    break;
                }

                $status = $this->faker->randomElement(['Hadir', 'Hadir', 'Hadir', 'Hadir', 'Hadir', 'Izin', 'Sakit', 'Alpa']);
                $waktuAbsen = ($status === 'Hadir') ? $currentDate->format('Y-m-d') . ' ' . $kelas->jam_mulai : null;
                $keterangan = ($status !== 'Hadir') ? $this->faker->sentence(3) : null;
                $isTerlaksana = true; // Assume all scheduled meetings are held

                try {
                    Absensi::create([
                        'kelas_id' => $kelas->id_kelas,
                        'mahasiswa_id' => $mahasiswaNim,
                        'status' => $status,
                        'waktu_absen' => $waktuAbsen,
                        'keterangan' => $keterangan,
                        'pertemuan_ke' => $i,
                        'tanggal' => $currentDate->toDateString(),
                        'materi' => $this->faker->sentence(4),
                        'is_terlaksana' => $isTerlaksana,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                    $totalAbsensiRecords++;
                    $meetingsScheduled++;
                } catch (\Illuminate\Database\QueryException $e) {
                    if (str_contains($e->getMessage(), 'unique_absensi_kelas_mahasiswa')) {
                        // This unique constraint sometimes causes issues with large datasets and random generation
                        // For seeding, it's often acceptable to skip duplicates.
                        // In a real app, you'd likely update or ensure unique generation.
                        // $this->command->warn("Duplicate absensi entry for kelas {$kelas->id_kelas} and mahasiswa {$mahasiswaNim} on {$currentDate->toDateString()}. Skipping.");
                    } else {
                        throw $e; // Re-throw other exceptions
                    }
                }
                $currentDate->addWeek(); // Move to the next week for the same day
            }
        }
        $this->command->info("Absensi Seeding Completed! Total records: {$totalAbsensiRecords}");
    }
}