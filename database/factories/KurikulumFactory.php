<?php

namespace Database\Factories; // <--- THIS MUST BE CORRECT

use App\Models\Kurikulum;
use App\Models\ProgramStudi; // Ensure this is used if ProgramStudi::factory() is called
use Illuminate\Database\Eloquent\Factories\Factory;

class KurikulumFactory extends Factory
{
    protected $model = Kurikulum::class;

    public function definition()
    {
        return [
            'kode_kurikulum' => $this->faker->unique()->bothify('KUR-####??'),
            'nama_kurikulum' => 'Kurikulum ' . $this->faker->year() . ' ' . $this->faker->words(2, true),
            'tahun_berlaku' => $this->faker->year(),
            'prodi_id' => ProgramStudi::factory(), // This will try to find ProgramStudiFactory
            'is_active' => $this->faker->boolean(90),
        ];
    }

    public function down()
    {
        Schema::dropIfExists('kurikulum');
    }
};