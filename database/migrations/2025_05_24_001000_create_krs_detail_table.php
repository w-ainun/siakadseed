<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('krs_detail', function (Blueprint $table) {
            $table->id('id_krsdetail');
            $table->foreignId('krs_id')->constrained('krs', 'id_krs')->onDelete('cascade');
            $table->foreignId('kelas_id')->constrained('kelas', 'id_kelas')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['krs_id', 'kelas_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('krs_detail');
    }
};