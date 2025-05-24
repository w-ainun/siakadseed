<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NilaiAkhir extends Model
{
    use HasFactory;

    protected $table = 'nilai_akhir';
    protected $primaryKey = 'id_nilaiakhir';
    protected $guarded = [];

    protected $casts = [
        'nilai_angka' => 'decimal:2',
    ];

    public function krsDetail()
    {
        return $this->belongsTo(KrsDetail::class, 'krs_detail_id', 'id_krsdetail');
    }
}