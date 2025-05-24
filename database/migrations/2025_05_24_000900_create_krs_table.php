<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('krs', function (Blueprint $table) {
            $table->id('id_krs');

            // Mengubah mahasiswa_id agar sesuai dengan tipe mahasiswa.nim (string)
            $table->string('mahasiswa_id', 20); // Sesuaikan panjang '20' jika nim di tabel mahasiswa berbeda
            $table->foreign('mahasiswa_id')->references('nim')->on('mahasiswa')->onDelete('cascade');

            // Kolom lain tetap sama, asumsi foreign key ke tahun_akademik sudah benar
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademik', 'id_tahunakademik')->onDelete('cascade');
            $table->dateTime('tanggal_pengajuan')->useCurrent();
            $table->dateTime('tanggal_persetujuan')->nullable();
            $table->enum('status', ['Draft', 'Diajukan', 'Disetujui', 'Ditolak'])->default('Draft');
            $table->text('catatan')->nullable();
            $table->integer('total_sks')->default(0);
            $table->timestamps();

            $table->unique(['mahasiswa_id', 'tahun_akademik_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('krs');
    }
};