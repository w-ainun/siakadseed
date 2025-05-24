<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\ProgramStudi; // Pastikan model ProgramStudi ada dan memiliki factory
use App\Models\Dosen;       // Pastikan model Dosen ada dan memiliki factory
use Illuminate\Database\Eloquent\Factories\Factory;

class MahasiswaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Mahasiswa::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Catatan: Pembuatan ProgramStudi dan Dosen di sini akan terjadi jika factory
        // dipanggil tanpa menyediakan 'prodi_id' atau 'dosen_pa_id' secara eksplisit.
        // Jika digunakan dengan MahasiswaSeeder Anda sebelumnya, nilai dari Seeder akan di-override.
        
        // Baris ini akan membuat ProgramStudi baru setiap kali factory Mahasiswa dipanggil,
        // kecuali 'prodi_id' di-override. Untuk seeding massal, Seeder Anda sudah menangani
        // pengambilan prodi_id yang ada, yang lebih efisien.
        $prodi = ProgramStudi::factory()->create(); 

        return [
            // 'nim' diasumsikan auto-incrementing atau diatur oleh logika lain jika diperlukan.
            
            // Menggunakan firstName() dan lastName() untuk menghindari gelar.
            'nama_mahasiswa' => $this->faker->firstName() . ' ' . $this->faker->lastName(), 
            
            'tempat_lahir' => $this->faker->city(),
            'tanggal_lahir' => $this->faker->date(),
            'jenis_kelamin' => $this->faker->randomElement(['L', 'P']),
            'agama' => $this->faker->randomElement(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']),
            'alamat' => $this->faker->address(),
            'no_telepon' => $this->faker->phoneNumber(),
            'email_pribadi' => $this->faker->unique()->safeEmail(),
            
            // Nilai-nilai berikut kemungkinan besar akan di-override oleh MahasiswaSeeder Anda:
            'tahun_masuk' => $this->faker->year(), 
            'status' => $this->faker->randomElement(['Aktif', 'Cuti', 'Drop Out', 'Lulus', 'Keluar']),
            'prodi_id' => $prodi->id, 
            
            // Membuat Dosen baru (80% kemungkinan) yang berelasi dengan prodi yang baru dibuat di atas,
            // kecuali 'dosen_pa_id' di-override.
            'dosen_pa_id' => $this->faker->boolean(80) ? Dosen::factory()->create(['prodi_id' => $prodi->id])->id : null,
        ];
    }
}