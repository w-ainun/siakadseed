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
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa', 'nim')->onDelete('cascade');
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