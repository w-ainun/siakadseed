<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ipk', function (Blueprint $table) {
            $table->id('id_ipk');

            // Mengubah mahasiswa_id agar sesuai dengan tipe mahasiswa.nim (string)
            // dan mempertahankan unique constraint
            $table->string('mahasiswa_id', 20)->unique(); // Sesuaikan panjang '20' jika nim di tabel mahasiswa berbeda
            $table->foreign('mahasiswa_id')->references('nim')->on('mahasiswa')->onDelete('cascade');

            // Kolom lain tetap sama
            $table->decimal('ipk', 3, 2); // e.g. 3.75
            $table->integer('total_sks');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ipk');
    }
};