<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KrsDetail extends Model
{
    use HasFactory;

    protected $table = 'krs_detail';
    protected $primaryKey = 'id_krsdetail';
    protected $guarded = [];

    public function krs()
    {
        return $this->belongsTo(Krs::class, 'krs_id', 'id_krs');
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id', 'id_kelas');
    }

    public function nilais()
    {
        return $this->hasMany(Nilai::class, 'krs_detail_id', 'id_krsdetail');
    }

    public function nilaiAkhir()
    {
        return $this->hasOne(NilaiAkhir::class, 'krs_detail_id', 'id_krsdetail');
    }
}