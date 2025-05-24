<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fakultas', function (Blueprint $table) {
            $table->id('id_fakultas');
            $table->string('nama_fakultas', 100);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fakultas');
    }
};