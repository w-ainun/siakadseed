<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('nilai_akhir', function (Blueprint $table) {
            $table->id('id_nilaiakhir');
            $table->foreignId('krs_detail_id')->constrained('krs_detail', 'id_krsdetail')->onDelete('cascade');
            $table->decimal('nilai_angka', 5, 2);
            $table->char('nilai_huruf', 2);
            $table->timestamps();

            $table->unique('krs_detail_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('nilai_akhir');
    }
};