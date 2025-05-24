<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('absensi', function (Blueprint $table) {
            $table->id('id_absensi');

            // Asumsi kelas.id_kelas adalah unsignedBigInteger (dibuat dengan $table->id('id_kelas'))
            $table->foreignId('kelas_id')->constrained('kelas', 'id_kelas')->onDelete('cascade');

            // Mengubah mahasiswa_id agar sesuai dengan tipe mahasiswa.nim (string)
            $table->string('mahasiswa_id', 20); // Sesuaikan panjang '20' jika nim di tabel mahasiswa berbeda
            $table->foreign('mahasiswa_id')->references('nim')->on('mahasiswa')->onDelete('cascade');

            // Kolom lain tetap sama
            $table->enum('status', ['Hadir', 'Izin', 'Sakit', 'Alpa'])->default('Alpa');
            $table->dateTime('waktu_absen')->nullable();
            $table->text('keterangan')->nullable();
            $table->integer('pertemuan_ke');
            $table->date('tanggal');
            $table->string('materi', 255);
            $table->boolean('is_terlaksana');
            $table->timestamps();

            // Unique key seperti yang Anda definisikan
            $table->unique(['kelas_id', 'mahasiswa_id'], 'unique_absensi_kelas_mahasiswa');
        });
    }

    public function down()
    {
        Schema::dropIfExists('absensi');
    }
};