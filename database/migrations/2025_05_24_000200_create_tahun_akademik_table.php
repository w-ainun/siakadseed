<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tahun_akademik', function (Blueprint $table) {
            $table->id('id_tahunakademik');
            $table->string('tahun_akademik', 10);
            $table->enum('semester', ['Ganjil', 'Genap', 'Pendek']);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['tahun_akademik', 'semester']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tahun_akademik');
    }
};