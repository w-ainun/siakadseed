<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mata_kuliah', function (Blueprint $table) {
            $table->id('kode_matakuliah'); // As per SQL: kode_matakuliah INT NOT NULL AUTO_INCREMENT
            $table->string('nama_mk', 100);
            $table->integer('sks');
            $table->integer('semester');
            $table->enum('jenis', ['Wajib', 'Pilihan'])->default('Wajib');
            $table->foreignId('kurikulum_id')->constrained('kurikulum', 'id_kurikulum')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mata_kuliah');
    }
};