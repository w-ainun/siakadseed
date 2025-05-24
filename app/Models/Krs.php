<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Krs extends Model
{
    use HasFactory;

    protected $table = 'krs';
    protected $primaryKey = 'id_krs';
    protected $guarded = [];

    protected $casts = [
        'tanggal_pengajuan' => 'datetime',
        'tanggal_persetujuan' => 'datetime',
        'total_sks' => 'integer',
    ];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'nim');
    }

    public function tahunAkademik()
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id', 'id_tahunakademik');
    }

    public function krsDetails()
    {
        return $this->hasMany(KrsDetail::class, 'krs_id', 'id_krs');
    }
}