<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mahasiswa extends Model
{
    use HasFactory;

    protected $table = 'mahasiswa';
    protected $primaryKey = 'nim'; // Primary key is 'nim'
    public $incrementing = true; // Since 'nim' is auto-incrementing in your schema
    protected $keyType = 'int';

    protected $guarded = [];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tahun_masuk' => 'integer', // Or 'year' if you handle it that way
    ];

    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id', 'id_prodi');
    }

    public function dosenPa()
    {
        return $this->belongsTo(Dosen::class, 'dosen_pa_id', 'id_dosen');
    }

    public function krs()
    {
        return $this->hasMany(Krs::class, 'mahasiswa_id', 'nim');
    }

    public function ips()
    {
        return $this->hasMany(Ips::class, 'mahasiswa_id', 'nim');
    }

    public function ipk()
    {
        // Assuming one IPK record per mahasiswa after they graduate or at certain points
        return $this->hasOne(Ipk::class, 'mahasiswa_id', 'nim');
    }

    public function absensi()
    {
        return $this->hasMany(Absensi::class, 'mahasiswa_id', 'nim');
    }
}