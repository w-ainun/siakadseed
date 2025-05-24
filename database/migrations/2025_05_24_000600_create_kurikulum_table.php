<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kurikulum', function (Blueprint $table) {
            $table->id('id_kurikulum');
            $table->string('kode_kurikulum', 20)->unique();
            $table->string('nama_kurikulum', 100);
            $table->year('tahun_berlaku');
            $table->foreignId('prodi_id')->constrained('program_studi', 'id_prodi')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kurikulum');
    }
};