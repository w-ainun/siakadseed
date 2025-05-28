<?php

 namespace Database\Seeders;

 use Illuminate\Database\Seeder;
 use App\Models\Mahasiswa;
 use App\Models\TahunAkademik;
 use App\Models\Kelas;
 use App\Models\Krs;
 use App\Models\KrsDetail;
 use Illuminate\Support\Facades\DB;
 use Illuminate\Support\Facades\Log;
 use Carbon\Carbon;
 use Faker\Factory as FakerFactory;

 class KrsSeeder extends Seeder
 {
     protected $faker;
     protected $batchSize = 1000; // Ukuran batch untuk insert KRS Detail
     protected $mahasiswaChunkSize = 1000; // Ukuran chunk untuk memproses Mahasiswa

     public function __construct()
     {
         $this->faker = FakerFactory::create('id_ID');
     }

     public function run()
     {
         $this->command->info('Memulai seeding data KRS...');

         // --- Membersihkan data yang ada ---
         $this->command->info('Membersihkan data KRS dan KRS Detail yang ada...');
         DB::statement('SET FOREIGN_KEY_CHECKS=0;');
         DB::table('krs_detail')->truncate();
         DB::table('krs')->truncate();
         DB::statement('SET FOREIGN_KEY_CHECKS=1;');
         $this->command->info('Data lama berhasil dihapus.');

         // --- Memuat data master (hanya sekali) ---
         $this->command->info('Memuat data master (Tahun Akademik, Kelas)...');
         $tahunAkademiks = TahunAkademik::orderBy('tahun_akademik')->orderByRaw("FIELD(semester, 'Ganjil', 'Genap')")->get();
         // Load MataKuliah untuk SKS calculation
         $kelasList = Kelas::with('mataKuliah')->get()->keyBy('id_kelas');
         $this->command->info('Data master berhasil dimuat.');

         if ($tahunAkademiks->isEmpty() || $kelasList->isEmpty()) {
             $this->command->error('Data dasar (Tahun Akademik, Kelas) tidak ditemukan. Seeder dihentikan.');
             return;
         }

         // Inisialisasi progress bar untuk total mahasiswa
         // Kita akan mendapatkan total mahasiswa terlebih dahulu
         $totalMahasiswa = Mahasiswa::count();
         $progressBar = $this->command->getOutput()->createProgressBar($totalMahasiswa);
         $progressBar->setFormatDefinition('custom', ' %current%/%max% [%bar%] %percent:3s%% -- %message% (Est: %estimated:-6s% Left: %remaining:-6s%)');
         $progressBar->setFormat('custom');
         $progressBar->start();
         $progressBar->setMessage('Mulai memproses mahasiswa untuk pembuatan KRS...');

         // Tahun angkatan yang sudah lulus
         $tahunAngkatanLulus = [2019, 2020];

         $krsDetailsToInsert = [];
         $krsUpdates = []; // Mengumpulkan data untuk update total_sks secara batch

         // --- Memproses Mahasiswa dalam chunk untuk efisiensi memori ---
         Mahasiswa::chunkById($this->mahasiswaChunkSize, function ($mahasiswasChunk) use (
             $tahunAkademiks,
             $kelasList,
             $progressBar,
             $tahunAngkatanLulus,
             &$krsDetailsToInsert, // Gunakan reference untuk memodifikasi array di luar closure
             &$krsUpdates
         ) {
             foreach ($mahasiswasChunk as $mahasiswa) {
                 // Langsung lewati mahasiswa jika tahun masuknya ada di daftar angkatan yang sudah lulus
                 if (in_array((int)$mahasiswa->tahun_masuk, $tahunAngkatanLulus)) {
                     $progressBar->setMessage("Melewatkan Mahasiswa angkatan {$mahasiswa->tahun_masuk} (sudah lulus): {$mahasiswa->nama} ({$mahasiswa->nim})");
                     $progressBar->advance();
                     continue; // Lanjutkan ke mahasiswa berikutnya
                 }

                 $progressBar->setMessage("Memproses KRS untuk Mahasiswa: {$mahasiswa->nama} ({$mahasiswa->nim})");

                 $tahunMasuk = (int)$mahasiswa->tahun_masuk;
                 $relevantTahunAkademiks = $tahunAkademiks->filter(function ($ta) use ($tahunMasuk) {
                     $taStartYear = (int)explode('/', $ta->tahun_akademik)[0];
                     return $taStartYear >= $tahunMasuk;
                 });

                 $semestersProcessedCount = 0;

                 foreach ($relevantTahunAkademiks as $ta) {
                     if ($semestersProcessedCount >= 8) { // Batasi semester per mahasiswa
                         break;
                     }

                     // 80% kemungkinan mahasiswa mengambil KRS
                     if ($this->faker->boolean(80)) {
                         $relevantKelasForProdi = $kelasList->filter(function ($kelas) use ($mahasiswa, $ta) {
                             return $kelas->tahun_akademik_id == $ta->id_tahunakademik &&
                                     ($kelas->mataKuliah->kurikulum->prodi_id ?? null) == $mahasiswa->prodi_id;
                         });

                         if ($relevantKelasForProdi->isEmpty()) {
                             Log::warning("Tidak ada kelas yang relevan untuk Mahasiswa {$mahasiswa->nim} di TA {$ta->id_tahunakademik}. KRS dilewati.");
                             continue;
                         }

                         $selectedKelas = $relevantKelasForProdi->shuffle()->take($this->faker->numberBetween(5, 8));

                         if ($selectedKelas->isEmpty()) {
                                 Log::warning("Tidak ada kelas yang dipilih untuk Mahasiswa {$mahasiswa->nim} di TA {$ta->id_tahunakademik} setelah shuffle. KRS dilewati.");
                             continue;
                         }

                         // Daripada insertGetId() di setiap iterasi, kita akan mengumpulkan KRS
                         // dan menginsertnya secara batch di akhir chunk.
                         // Ini akan membutuhkan ID KRS yang sebenarnya setelah insert.
                         // Untuk sementara, kita pakai placeholder atau generate ID unik jika diperlukan
                         // atau kita bisa memisahkan insert KRS dan KRS Detail.

                         // Strategi baru: Insert KRS terlebih dahulu, lalu krs_detail secara batch
                         // Ini karena kita butuh id_krs untuk krs_detail
                         $krsId = DB::table('krs')->insertGetId([
                             'mahasiswa_id' => $mahasiswa->nim,
                             'tahun_akademik_id' => $ta->id_tahunakademik,
                             'tanggal_pengajuan' => $this->faker->dateTimeBetween($ta->tanggal_mulai, Carbon::parse($ta->tanggal_mulai)->addMonth(1))->format('Y-m-d H:i:s'),
                             'status' => 'Disetujui',
                             'total_sks' => 0, // Akan diperbarui nanti
                             'tanggal_persetujuan' => now()->format('Y-m-d H:i:s'),
                             'created_at' => now()->format('Y-m-d H:i:s'),
                             'updated_at' => now()->format('Y-m-d H:i:s'),
                         ]);

                         $currentKrsSks = 0;
                         foreach ($selectedKelas as $kelas) {
                             $krsDetailsToInsert[] = [
                                 'krs_id' => $krsId,
                                 'kelas_id' => $kelas->id_kelas,
                                 'created_at' => now()->format('Y-m-d H:i:s'),
                                 'updated_at' => now()->format('Y-m-d H:i:s'),
                             ];
                             $currentKrsSks += $kelas->mataKuliah->sks ?? 0;
                         }

                         // Kumpulkan KRS ID dan total SKS untuk update batch
                         $krsUpdates[$krsId] = $currentKrsSks;

                         // Batch insert KRS Details
                         if (count($krsDetailsToInsert) >= $this->batchSize) {
                             $progressBar->setMessage("Menyimpan " . count($krsDetailsToInsert) . " detail KRS ke database...");
                             DB::table('krs_detail')->insert($krsDetailsToInsert);
                             $krsDetailsToInsert = [];
                         }

                         $semestersProcessedCount++;
                     }
                 }
                 $progressBar->advance();
             }

             // Insert sisa KRS Details setelah setiap chunk mahasiswa selesai
             if (!empty($krsDetailsToInsert)) {
                 $this->command->info("Menyimpan sisa " . count($krsDetailsToInsert) . " detail KRS dari chunk ke database...");
                 DB::table('krs_detail')->insert($krsDetailsToInsert);
                 $krsDetailsToInsert = [];
             }
         }); // End of Mahasiswa::chunkById

         $progressBar->finish();
         $this->command->info("\nSelesai memproses mahasiswa dan membuat entri KRS.");

         // --- Memperbarui total SKS untuk semua entri KRS secara batch ---
         $this->command->info("Memulai pembaruan total SKS untuk semua entri KRS...");
         $updateProgressBar = $this->command->getOutput()->createProgressBar(count($krsUpdates));
         $updateProgressBar->setFormatDefinition('custom', ' %current%/%max% [%bar%] %percent:3s%% -- %message% (Est: %estimated:-6s% Left: %remaining:-6s%)');
         $updateProgressBar->setFormat('custom');
         $updateProgressBar->start();
         $updateProgressBar->setMessage("Memulai pembaruan total SKS...");

         // Melakukan update SKS dalam batch
         $krsUpdateBatch = [];
         foreach ($krsUpdates as $krsId => $totalSks) {
             $krsUpdateBatch[] = ['id_krs' => $krsId, 'total_sks' => $totalSks];

             if (count($krsUpdateBatch) >= $this->batchSize) {
                 DB::table('krs')->upsert(
                     $krsUpdateBatch,
                     ['id_krs'], // Kunci unik
                     ['total_sks'] // Kolom yang diupdate
                 );
                 $updateProgressBar->advance(count($krsUpdateBatch));
                 $krsUpdateBatch = [];
             }
         }

         // Update sisa batch
         if (!empty($krsUpdateBatch)) {
             DB::table('krs')->upsert(
                 $krsUpdateBatch,
                 ['id_krs'],
                 ['total_sks']
             );
             $updateProgressBar->advance(count($krsUpdateBatch));
         }

         $updateProgressBar->finish();
         $this->command->info("\nSelesai memperbarui total SKS.");

         $this->command->info("\nSeeding KRS selesai.");
     }
 }