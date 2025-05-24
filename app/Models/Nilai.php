<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Nilai extends Model
{
    use HasFactory;

    protected $table = 'nilai';
    protected $primaryKey = 'id_nilai';
    protected $guarded = [];

    protected $casts = [
        'nilai_angka' => 'decimal:2',
    ];

    public function krsDetail()
    {
        return $this->belongsTo(KrsDetail::class, 'krs_detail_id', 'id_krsdetail');
    }

    public function komponenNilai()
    {
        return $this->belongsTo(KomponenNilai::class, 'komponen_nilai_id', 'id_komponennilai');
    }
}