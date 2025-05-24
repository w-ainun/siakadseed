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
            $table->foreignId('mahasiswa_id')->constrained('mahasiswa', 'nim')->onDelete('cascade')->unique();
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