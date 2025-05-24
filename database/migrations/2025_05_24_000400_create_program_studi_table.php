<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('program_studi', function (Blueprint $table) {
            $table->id('id_prodi');
            $table->string('nama_prodi', 100);
            $table->enum('jenjang', ['D3', 'S1', 'S2', 'S3']);
            $table->foreignId('fakultas_id')->constrained('fakultas', 'id_fakultas')->onDelete('restrict');
            $table->unsignedBigInteger('kaprodi_id')->nullable(); // Define column, FK added later
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('program_studi');
    }
};