<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ipk extends Model
{
    use HasFactory;

    protected $table = 'ipk';
    protected $primaryKey = 'id_ipk';
    protected $guarded = [];

    protected $casts = [
        'ipk' => 'decimal:2',
        'total_sks' => 'integer',
    ];

    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id', 'nim');
    }
}