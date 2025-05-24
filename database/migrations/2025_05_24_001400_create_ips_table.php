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
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa', 'nim')->onDelete('cascade');
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