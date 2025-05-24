<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use Faker\Factory as FakerFactoryInstance; // Beri nama alias untuk menghindari konflik jika ada kelas Faker lain
use Illuminate\Database\Eloquent\Factories\Factory;

class MahasiswaFactory extends Factory
{
    protected $model = Mahasiswa::class;

    // Daftar kota di Indonesia untuk tempat lahir
    // Sebaiknya ini disimpan di config atau helper jika digunakan di banyak tempat
    protected $indonesianCities = [
        'Jakarta', 'Surabaya', 'Bandung', 'Medan', 'Semarang', 'Palembang', 'Makassar',
        'Batam', 'Pekanbaru', 'Bogor', 'Padang', 'Malang', 'Bandar Lampung', 'Denpasar',
        'Samarinda', 'Tasikmalaya', 'Serang', 'Banjarmasin', 'Pontianak', 'Cimahi',
        'Balikpapan', 'Jambi', 'Surakarta', 'Mataram', 'Manado', 'Yogyakarta', 'Cilegon',
        'Kupang', 'Palu', 'Ambon', 'Tarakan', 'Sukabumi', 'Cirebon', 'Pekalongan', 'Kediri',
        'Pematangsiantar', 'Tegal', 'Sorong', 'Binjai', 'Dumai', 'Palangka Raya', 'Singkawang',
        'Probolinggo', 'Padang Sidempuan', 'Bitung', 'Banjarbaru', 'Lubuklinggau', 'Ternate',
        'Prabumulih', 'Tanjungpinang', 'Gorontalo', 'Bau-Bau', 'Madiun', 'Salatiga', 'Pangkalpinang',
        'Lhokseumawe', 'Bima', 'Mojokerto', 'Magelang', 'Kotamobagu', 'Parepare', 'Blitar',
        'Gunungsitoli', 'Tidore Kepulauan', 'Pagar Alam', 'Payakumbuh', 'Langsa', 'Bukittinggi',
        'Pasuruan', 'Tanjungbalai', 'Metro', 'Bontang', 'Baubau', 'Kendari', 'Sibolga', 'Solok',
        'Tomohon', 'Subulussalam', 'Sungai Penuh'
    ];

    // Inisialisasi Faker dengan lokal Indonesia di sini agar tidak perlu di seeder
    protected $fakerID;

    public function __construct()
    {
        parent::__construct();
        $this->fakerID = FakerFactoryInstance::create('id_ID');
    }

    public function definition()
    {
        // Seeder akan menyediakan: nim, tahun_masuk, prodi_id, dosen_pa_id, status
        // Factory ini akan mengisi sisanya:
        $gender = $this->fakerID->randomElement(['Laki-laki', 'Perempuan']);
        $firstName = $gender === 'Laki-laki' ? $this->fakerID->firstNameMale : $this->fakerID->firstNameFemale;
        $lastName = $this->fakerID->lastName;

        // Ambil tahun_masuk dari state yang mungkin di-override oleh seeder, default ke rentang umum jika tidak ada
        $tahunMasuk = $this->attributes['tahun_masuk'] ?? $this->fakerID->numberBetween(2000, (int)date('Y') - 18);
        $birthYearStart = $tahunMasuk - 20; // Perkiraan usia masuk kuliah 17-19 thn
        $birthYearEnd = $tahunMasuk - 17;

        return [
            'nama_mahasiswa' => $firstName . ' ' . $lastName,
            'jenis_kelamin' => $gender,
            'tempat_lahir' => $this->fakerID->randomElement($this->indonesianCities),
            'tanggal_lahir' => $this->fakerID->dateTimeBetween("{$birthYearStart}-01-01", "{$birthYearEnd}-12-31")->format('Y-m-d'),
            'agama' => $this->fakerID->randomElement(['Islam', 'Kristen Protestan', 'Katolik', 'Hindu', 'Buddha', 'Khonghucu']),
            'nik' => $this->fakerID->unique()->numerify('################'), // 16 digit NIK
            'email_pribadi' => $this->fakerID->unique()->safeEmail,
            'no_telepon' => '08' . $this->fakerID->numerify($this->fakerID->randomElement(['##########', '###########'])), // 10-11 digits after 08
            'alamat_asal' => $this->fakerID->address,
            'alamat_domisili' => $this->fakerID->optional(0.7)->address, // 70% kemungkinan punya alamat domisili beda
            'nama_ayah' => $this->fakerID->name('male'),
            'nama_ibu' => $this->fakerID->name('female'),
            'pekerjaan_ayah' => $this->fakerID->jobTitle,
            'pekerjaan_ibu' => $this->fakerID->jobTitle,
            'no_telepon_orang_tua' => '08' . $this->fakerID->numerify($this->fakerID->randomElement(['##########', '###########'])),
            'jalur_masuk' => $this->fakerID->randomElement(['SNBP', 'SNBT', 'Mandiri']),
            'sma_asal' => 'SMA ' . $this->fakerID->randomElement(['Negeri', 'Swasta']) . ' ' . $this->fakerID->numberBetween(1,10) . ' ' . $this->fakerID->city,
            'tahun_lulus_sma' => $tahunMasuk - $this->fakerID->numberBetween(0,1), // Lulus SMA tahun yg sama atau setahun sebelumnya
            // 'foto_mahasiswa' => null, // Atau gunakan $this->faker->imageUrl() jika perlu
            // created_at dan updated_at akan diisi otomatis oleh Eloquent
        ];
    }
}