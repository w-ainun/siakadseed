<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dosen extends Model
{
    use HasFactory;

    protected $table = 'dosen';
    protected $primaryKey = 'id_dosen';
    protected $guarded = [];

    protected $casts = [
        'tanggal_lahir' => 'date',
    ];

    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id', 'id_prodi');
    }

    public function prodiSebagaiKaprodi()
    {
        return $this->hasOne(ProgramStudi::class, 'kaprodi_id', 'id_dosen');
    }

    public function mahasiswaAsPa()
    {
        return $this->hasMany(Mahasiswa::class, 'dosen_pa_id', 'id_dosen');
    }

    public function kelasMengajar()
    {
        return $this->hasMany(Kelas::class, 'dosen_id', 'id_dosen');
    }
}