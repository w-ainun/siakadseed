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

    // This table only has 'created_at' as per your SQL and migration.
    // To tell Laravel about this:
    public $timestamps = true; // Allow Laravel to manage created_at
    const UPDATED_AT = null; // Indicate that there is no updated_at column

    // Alternatively, if you don't want Laravel to manage any timestamps automatically for this model:
    // public $timestamps = false;
    // protected $casts = [
    //    'created_at' => 'datetime', // If you want to cast it when fetching
    // ];

    public function mataKuliah()
    {
        return $this->belongsTo(MataKuliah::class, 'mk_id', 'kode_matakuliah');
    }

    public function mataKuliahPrasyarat()
    {
        return $this->belongsTo(MataKuliah::class, 'mk_prasyarat_id', 'kode_matakuliah');
    }
}