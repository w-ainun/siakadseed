<?php

namespace Database\Factories;

use App\Models\Ipk;
use App\Models\Mahasiswa;
use Illuminate\Database\Eloquent\Factories\Factory;

class IpkFactory extends Factory
{
    protected $model = Ipk::class;

    public function definition()
    {
        return [
            'mahasiswa_id' => Mahasiswa::factory(),
            'ipk' => $this->faker->randomFloat(2, 2.00, 4.00),
            'total_sks' => $this->faker->numberBetween(24, 144),
        ];
    }
}