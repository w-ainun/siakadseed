<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramStudi extends Model
{
    use HasFactory;

    protected $table = 'program_studi';
    protected $primaryKey = 'id_prodi';
    protected $guarded = [];

    public function fakultas()
    {
        return $this->belongsTo(Fakultas::class, 'fakultas_id', 'id_fakultas');
    }

    public function kaprodi()
    {
        return $this->belongsTo(Dosen::class, 'kaprodi_id', 'id_dosen');
    }

    public function dosens()
    {
        return $this->hasMany(Dosen::class, 'prodi_id', 'id_prodi');
    }

    public function mahasiswas()
    {
        return $this->hasMany(Mahasiswa::class, 'prodi_id', 'id_prodi');
    }

    public function kurikulums()
    {
        return $this->hasMany(Kurikulum::class, 'prodi_id', 'id_prodi');
    }
}