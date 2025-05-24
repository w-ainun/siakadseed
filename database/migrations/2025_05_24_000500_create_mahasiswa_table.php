<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mahasiswa', function (Blueprint $table) {
            $table->id('nim'); // As per SQL: nim INT NOT NULL AUTO_INCREMENT
            $table->string('nama_mahasiswa', 100);
            $table->string('tempat_lahir', 50)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->string('agama', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_telepon', 20)->nullable();
            $table->string('email_pribadi', 100)->nullable()->unique();
            $table->year('tahun_masuk')->nullable();
            $table->enum('status', ['Aktif', 'Cuti', 'Drop Out', 'Lulus', 'Keluar'])->default('Aktif');
            $table->foreignId('prodi_id')->constrained('program_studi', 'id_prodi')->onDelete('restrict');
            $table->foreignId('dosen_pa_id')->nullable()->constrained('dosen', 'id_dosen')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mahasiswa');
    }
};