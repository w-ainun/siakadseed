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
            $table->foreignId('kelas_id')->constrained('kelas', 'id_kelas')->onDelete('cascade');
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa', 'nim')->onDelete('cascade');
            $table->enum('status', ['Hadir', 'Izin', 'Sakit', 'Alpa'])->default('Alpa');
            $table->dateTime('waktu_absen')->nullable();
            $table->text('keterangan')->nullable();
            $table->integer('pertemuan_ke');
            $table->date('tanggal');
            $table->string('materi', 255);
            $table->boolean('is_terlaksana'); // tinyint in SQL is often boolean
            $table->timestamps();

            // SQL: ADD UNIQUE KEY `jadwal_pertemuan_id` (`kelas_id`,`mahasiswa_id`)
            // This constraint is unusual for a per-meeting attendance table, as it would allow only one
            // attendance record per student for an entire class, regardless of 'pertemuan_ke'.
            // A more typical unique key would be ['kelas_id', 'mahasiswa_id', 'pertemuan_ke'].
            // Implementing as per SQL provided:
            $table->unique(['kelas_id', 'mahasiswa_id'], 'unique_absensi_kelas_mahasiswa');
        });
    }

    public function down()
    {
        Schema::dropIfExists('absensi');
    }
};