<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TahunAkademik extends Model
{
    use HasFactory;

    protected $table = 'tahun_akademik';
    protected $primaryKey = 'id_tahunakademik';
    protected $guarded = [];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'is_active' => 'boolean',
    ];

    public function kelas()
    {
        return $this->hasMany(Kelas::class, 'tahun_akademik_id', 'id_tahunakademik');
    }

    public function krs()
    {
        return $this->hasMany(Krs::class, 'tahun_akademik_id', 'id_tahunakademik');
    }

    public function ips()
    {
        return $this->hasMany(Ips::class, 'tahun_akademik_id', 'id_tahunakademik');
    }
}