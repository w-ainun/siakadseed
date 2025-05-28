<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('komponen_nilai', function (Blueprint $table) {
            $table->id('id_komponennilai');
            $table->foreignId('kelas_id')->constrained('kelas', 'id_kelas')->onDelete('cascade');
            $table->string('nama_komponen', 50);
            $table->decimal('bobot', 5, 2); // e.g., 25.00 for 25%
            $table->string('temp_seed_uuid', 36)->nullable()->unique(); // <-- Tambahkan baris ini
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('komponen_nilai');
    }
};