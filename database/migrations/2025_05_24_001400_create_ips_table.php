<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ips', function (Blueprint $table) {
            $table->id('id_ips');

            // Mengubah mahasiswa_id agar sesuai dengan tipe mahasiswa.nim (string)
            $table->string('mahasiswa_id', 20); // Sesuaikan panjang '20' jika nim di tabel mahasiswa berbeda
            $table->foreign('mahasiswa_id')->references('nim')->on('mahasiswa')->onDelete('cascade');

            // Kolom lain tetap sama
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademik', 'id_tahunakademik')->onDelete('cascade');
            $table->decimal('ips', 3, 2); // e.g. 3.75
            $table->integer('total_sks');
            $table->timestamps();

            $table->unique(['mahasiswa_id', 'tahun_akademik_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ips');
    }
};