<?php

namespace Database\Factories;

use App\Models\Fakultas;
use Illuminate\Database\Eloquent\Factories\Factory;

class FakultasFactory extends Factory
{
    protected $model = Fakultas::class;

    public function definition()
    {
        return [
            'nama_fakultas' => 'Fakultas ' . $this->faker->bs(),
        ];
    }
}