<?php

namespace Database\Factories;

use App\Models\PrasyaratMk;
use App\Models\MataKuliah;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrasyaratMkFactory extends Factory
{
    protected $model = PrasyaratMk::class;

    public function definition()
    {
        // Ensure mk_id and mk_prasyarat_id are different.
        // This requires careful handling, especially if MataKuliah records are few or created on the fly.
        $mk1 = MataKuliah::factory()->create();
        $mk2 = MataKuliah::factory()->create();

        // A simple attempt to ensure they are different; in real seeding, you'd pick from existing, valid MKs.
        while ($mk1->kode_matakuliah === $mk2->kode_matakuliah) {
            $mk2 = MataKuliah::factory()->create();
        }

        return [
            'mk_id' => $mk1->kode_matakuliah,
            'mk_prasyarat_id' => $mk2->kode_matakuliah,
        ];
    }
}