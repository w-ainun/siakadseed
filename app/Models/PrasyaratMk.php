<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrasyaratMk extends Model
{
    use HasFactory;

    protected $table = 'prasyarat_mk';
    protected $primaryKey = 'id_prasyarat';
    protected $guarded = [];


    public $timestamps = true; 
    const UPDATED_AT = null; 

   

    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class, 'mk_id', 'kode_matakuliah');
    }

    public function mataKuliahPrasyarat()
    {
        return $this->belongsTo(MataKuliah::class, 'mk_prasyarat_id', 'kode_matakuliah');
    }
}