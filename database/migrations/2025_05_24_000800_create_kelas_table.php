<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id('id_kelas');
            $table->foreignId('tahun_akademik_id')->constrained('tahun_akademik', 'id_tahunakademik')->onDelete('cascade');
            $table->foreignId('mata_kuliah_id')->constrained('mata_kuliah', 'kode_matakuliah')->onDelete('cascade');
            $table->foreignId('dosen_id')->nullable()->constrained('dosen', 'id_dosen')->onDelete('set null');
            $table->enum('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu']);
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('ruangan', 50);
            $table->integer('kapasitas')->default(40);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // The SQL `UNIQUE KEY kode_kelas (tahun_akademik_id)` seems problematic as it would only allow
            // one class per academic year. This is likely not the intended behavior.
            // If a class code or name should be unique per academic year and subject,
            // a different combination of columns or a dedicated class code field would be needed.
            // For now, omitting this specific unique constraint as it's highly restrictive.
            // If it must be added: $table->unique('tahun_akademik_id', 'unique_kode_kelas_on_tahun');
        });
    }

    public function down()
    {
        Schema::dropIfExists('kelas');
    }
};