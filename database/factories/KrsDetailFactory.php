<?php

namespace Database\Factories;

use App\Models\KrsDetail;
use App\Models\Krs;
use App\Models\Kelas;
use Illuminate\Database\Eloquent\Factories\Factory;

class KrsDetailFactory extends Factory
{
    protected $model = KrsDetail::class;

    public function definition()
    {
        return [
            'krs_id' => Krs::factory(),
            'kelas_id' => Kelas::factory(),
        ];
    }
}