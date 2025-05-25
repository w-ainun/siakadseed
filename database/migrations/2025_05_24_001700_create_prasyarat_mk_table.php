<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('prasyarat_mk', function (Blueprint $table) {
            $table->id('id_prasyarat');
            $table->foreignId('mk_id')->constrained('mata_kuliah', 'kode_matakuliah')->onDelete('cascade');
            $table->foreignId('mk_prasyarat_id')->constrained('mata_kuliah', 'kode_matakuliah')->onDelete('cascade');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->unique(['mk_id', 'mk_prasyarat_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('prasyarat_mk');
    }
};