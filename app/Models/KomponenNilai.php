<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KomponenNilai extends Model
{
    use HasFactory;

    protected $table = 'komponen_nilai';
    protected $primaryKey = 'id_komponennilai';
    protected $guarded = [];

    protected $casts = [
        'bobot' => 'decimal:2',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'kelas_id', 'id_kelas');
    }

    public function nilais()
    {
        return $this->hasMany(Nilai::class, 'komponen_nilai_id', 'id_komponennilai');
    }
}