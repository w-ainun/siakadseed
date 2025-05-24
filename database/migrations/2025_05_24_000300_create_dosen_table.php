<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dosen', function (Blueprint $table) {
            $table->id('id_dosen');
            $table->string('nidn', 20)->unique();
            $table->string('nama_dosen', 100);
            $table->string('gelar_depan', 20)->nullable();
            $table->string('gelar_belakang', 50)->nullable();
            $table->string('tempat_lahir', 50)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_telepon', 20)->nullable();
            $table->enum('status', ['Aktif', 'Cuti', 'Keluar', 'Pensiun'])->default('Aktif');
            $table->unsignedBigInteger('prodi_id')->nullable(); // Define column, FK added later
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('dosen');
    }
};