<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MataKuliah extends Model
{
    use HasFactory;

    protected $table = 'mata_kuliah';
    protected $primaryKey = 'kode_matakuliah'; // Primary key is 'kode_matakuliah'
    public $incrementing = true; // Since it's auto-incrementing
    protected $keyType = 'int';

    protected $guarded = [];

    public function kurikulum()
    {
        return $this->belongsTo(Kurikulum::class, 'kurikulum_id', 'id_kurikulum');
    }

    public function kelas()
    {
        return $this->hasMany(Kelas::class, 'mata_kuliah_id', 'kode_matakuliah');
    }

    // Prasyarat where this MK is the main course
    public function prasyarats()
    {
        return $this->hasMany(PrasyaratMk::class, 'mk_id', 'kode_matakuliah');
    }

    // Prasyarat where this MK is the prerequisite course
    public function menjadiPrasyaratUntuk()
    {
        return $this->hasMany(PrasyaratMk::class, 'mk_prasyarat_id', 'kode_matakuliah');
    }
}