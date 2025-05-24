<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use Faker\Factory as FakerFactoryInstance; // Beri nama alias untuk menghindari konflik jika ada kelas Faker lain
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str; // Import Str facade untuk helper string

class MahasiswaFactory extends Factory
{
    protected $model = Mahasiswa::class;

    // Daftar kota di Indonesia untuk tempat lahir
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

    // Inisialisasi Faker dengan lokal Indonesia
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
        $gender = $this->fakerID->randomElement(['Laki-laki', 'Perempuan']); // Sesuai file MahasiswaFactory.php yang diunggah
        $firstName = $gender === 'Laki-laki' ? $this->fakerID->firstNameMale : $this->fakerID->firstNameFemale;
        $lastName = $this->fakerID->lastName;

        // Ambil tahun_masuk dari state yang mungkin di-override oleh seeder, default ke rentang umum jika tidak ada
        // Menggunakan nilai default dari file yang Anda unggah
        $tahunMasuk = $this->attributes['tahun_masuk'] ?? $this->fakerID->numberBetween(2000, (int)date('Y') - 18);
        $birthYearStart = $tahunMasuk - 20; // Perkiraan usia masuk kuliah 17-19 thn
        $birthYearEnd = $tahunMasuk - 17;

        // Membuat email lebih unik
        $emailUsername = strtolower(Str::slug($firstName . ' ' . $lastName, ''));
        // Menggunakan ->unique() pada fakerID instance yang sama akan mencoba melacak nilai yang sudah dihasilkan
        // dalam satu instance factory ini.
        $uniqueSuffix = $this->fakerID->unique()->numerify('#####'); // Tambah 5 digit angka acak unik
        $email = $emailUsername . $uniqueSuffix . '@example.com'; // Ganti example.com jika perlu domain lain

        return [
            'nama_mahasiswa' => $firstName . ' ' . $lastName, // Sesuai file MahasiswaFactory.php yang diunggah
            'jenis_kelamin' => $gender, // Sesuai file MahasiswaFactory.php yang diunggah
            'tempat_lahir' => $this->fakerID->randomElement($this->indonesianCities), // Sesuai file MahasiswaFactory.php yang diunggah
            'tanggal_lahir' => $this->fakerID->dateTimeBetween("{$birthYearStart}-01-01", "{$birthYearEnd}-12-31")->format('Y-m-d'), // Sesuai file MahasiswaFactory.php yang diunggah
            'agama' => $this->fakerID->randomElement(['Islam', 'Kristen Protestan', 'Katolik', 'Hindu', 'Buddha', 'Khonghucu']), // Sesuai file MahasiswaFactory.php yang diunggah
            'nik' => $this->fakerID->unique()->numerify('################'), // Sesuai file MahasiswaFactory.php yang diunggah
            'email_pribadi' => $email, // <<--- MODIFIKASI DI SINI untuk email yang lebih unik
            'no_telepon' => '08' . $this->fakerID->numerify($this->fakerID->randomElement(['##########', '###########'])), // Sesuai file MahasiswaFactory.php yang diunggah
            'alamat_asal' => $this->fakerID->address, // Sesuai file MahasiswaFactory.php yang diunggah
            'alamat_domisili' => $this->fakerID->optional(0.7)->address, // Sesuai file MahasiswaFactory.php yang diunggah
            'nama_ayah' => $this->fakerID->name('male'), // Sesuai file MahasiswaFactory.php yang diunggah
            'nama_ibu' => $this->fakerID->name('female'), // Sesuai file MahasiswaFactory.php yang diunggah
            'pekerjaan_ayah' => $this->fakerID->jobTitle, // Sesuai file MahasiswaFactory.php yang diunggah
            'pekerjaan_ibu' => $this->fakerID->jobTitle, // Sesuai file MahasiswaFactory.php yang diunggah
            'no_telepon_orang_tua' => '08' . $this->fakerID->numerify($this->fakerID->randomElement(['##########', '###########'])), // Sesuai file MahasiswaFactory.php yang diunggah
            'jalur_masuk' => $this->fakerID->randomElement(['SNBP', 'SNBT', 'Mandiri']), // Sesuai file MahasiswaFactory.php yang diunggah
            'sma_asal' => 'SMA ' . $this->fakerID->randomElement(['Negeri', 'Swasta']) . ' ' . $this->fakerID->numberBetween(1,10) . ' ' . $this->fakerID->city, // Sesuai file MahasiswaFactory.php yang diunggah
            'tahun_lulus_sma' => $tahunMasuk - $this->fakerID->numberBetween(0,1), // Sesuai file MahasiswaFactory.php yang diunggah
            // 'created_at' dan 'updated_at' akan diisi otomatis jika Anda menggunakan
            // Mahasiswa::factory()->createMany() dan tidak menggunakan Mahasiswa::insert() atau DB::table()->insert().
            // Jika Anda menggunakan Mahasiswa::insert() atau DB::table()->insert() di seeder,
            // Anda perlu menambahkan 'created_at' => now(), 'updated_at' => now() di sini atau di seeder.
            // Namun, kode seeder terakhir kita sudah menambahkan created_at dan updated_at secara manual saat menggunakan DB::table()->insert().
        ];
    }
}