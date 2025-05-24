<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ips extends Model
{
    use HasFactory;

    protected $table = 'ips';
    protected $primaryKey = 'id_ips';
    protected $guarded = [];

    protected $casts = [
        'ips' => 'decimal:2',
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
}