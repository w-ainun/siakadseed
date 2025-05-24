<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kurikulum extends Model
{
    use HasFactory;

    protected $table = 'kurikulum';
    protected $primaryKey = 'id_kurikulum';
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'tahun_berlaku' => 'integer',
    ];

    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id', 'id_prodi');
    }

    public function mataKuliahs()
    {
        return $this->hasMany(MataKuliah::class, 'kurikulum_id', 'id_kurikulum');
    }
}