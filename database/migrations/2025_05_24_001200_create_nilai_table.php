<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('nilai', function (Blueprint $table) {
            $table->id('id_nilai');
            $table->foreignId('krs_detail_id')->constrained('krs_detail', 'id_krsdetail')->onDelete('cascade');
            $table->foreignId('komponen_nilai_id')->constrained('komponen_nilai', 'id_komponennilai')->onDelete('cascade');
            $table->decimal('nilai_angka', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['krs_detail_id', 'komponen_nilai_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('nilai');
    }
};