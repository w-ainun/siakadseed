<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mahasiswa', function (Blueprint $table) {
            // NIM dari Seeder (format: 2024010010001), jadi string dan primary key
            $table->string('nim', 20)->primary(); // Sesuaikan panjang jika perlu (13 digit dari sprintf)

            $table->string('nama_mahasiswa', 100);
            $table->string('tempat_lahir', 50)->nullable();
            $table->date('tanggal_lahir')->nullable();
            // jenis_kelamin disesuaikan dengan output factory
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan'])->nullable();
            $table->string('agama', 20)->nullable();

            // Kolom 'alamat' diubah menjadi 'alamat_asal' sesuai factory, dan ditambahkan 'alamat_domisili'
            $table->text('alamat_asal')->nullable();
            $table->text('alamat_domisili')->nullable(); // Baru, dari factory

            $table->string('no_telepon', 20)->nullable();
            $table->string('email_pribadi', 100)->nullable()->unique();

            // Kolom NIK ditambahkan sesuai factory
            $table->string('nik', 16)->unique()->nullable(); // Baru, dari factory

            $table->year('tahun_masuk')->nullable(); // Diisi oleh Seeder
            $table->enum('status', ['Aktif', 'Cuti', 'Drop Out', 'Lulus', 'Keluar'])->default('Aktif'); // Diisi oleh Seeder

            $table->foreignId('prodi_id')->constrained('program_studi', 'id_prodi')->onDelete('restrict'); // Diisi oleh Seeder
            $table->foreignId('dosen_pa_id')->nullable()->constrained('dosen', 'id_dosen')->onDelete('set null'); // Diisi oleh Seeder

            // Kolom detail orang tua (Baru, dari factory)
            $table->string('nama_ayah', 100)->nullable();
            $table->string('nama_ibu', 100)->nullable();
            $table->string('pekerjaan_ayah', 100)->nullable();
            $table->string('pekerjaan_ibu', 100)->nullable();
            $table->string('no_telepon_orang_tua', 20)->nullable();

            // Kolom detail penerimaan dan sekolah (Baru, dari factory)
            $table->string('jalur_masuk', 50)->nullable();
            $table->string('sma_asal', 100)->nullable();
            $table->year('tahun_lulus_sma')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mahasiswa');
    }
};