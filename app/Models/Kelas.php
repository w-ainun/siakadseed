<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kelas extends Model
{
    use HasFactory;

    protected $table = 'kelas';
    protected $primaryKey = 'id_kelas';
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'kapasitas' => 'integer',
        // 'jam_mulai' => 'datetime:H:i:s', // Or handle as string
        // 'jam_selesai' => 'datetime:H:i:s',
    ];

    public function tahunAkademik()
    {
        return $this->belongsTo(TahunAkademik::class, 'tahun_akademik_id', 'id_tahunakademik');
    }

    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class, 'mata_kuliah_id', 'kode_matakuliah');
    }

    public function dosen()
    {
        return $this->belongsTo(Dosen::class, 'dosen_id', 'id_dosen');
    }

    public function krsDetails()
    {
        return $this->hasMany(KrsDetail::class, 'kelas_id', 'id_kelas');
    }

    public function komponenNilais()
    {
        return $this->hasMany(KomponenNilai::class, 'kelas_id', 'id_kelas');
    }

    public function absensi()
    {
        return $this->hasMany(Absensi::class, 'kelas_id', 'id_kelas');
    }
}