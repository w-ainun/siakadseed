<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mahasiswa extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang digunakan oleh model.
     */
    protected $table = 'mahasiswa';

    /**
     * Primary key untuk model.
     */
    protected $primaryKey = 'nim'; // 'nim' adalah primary key

    /**
     * Menunjukkan jika ID auto-incrementing.
     * KARENA 'nim' KITA ISI MANUAL (STRING), INI HARUS FALSE.
     */
    public $incrementing = false; // <--- PERBAIKAN UTAMA

    /**
     * Tipe data dari primary key.
     * KARENA 'nim' ADALAH STRING.
     */
    protected $keyType = 'string'; // <--- PERBAIKAN UTAMA

    /**
     * Atribut yang tidak dijaga dari mass assignment.
     * Menggunakan $guarded = [] berarti semua atribut bisa diisi massal.
     * Ini alternatif dari $fillable. Pastikan 'nim' bisa diisi.
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'tanggal_lahir' => 'date',
        'tahun_masuk' => 'integer', // Atau 'year' jika Anda menggunakan tipe 'year' di DB dan ingin cast khusus
        // Pastikan nama kolom di sini sesuai dengan nama kolom di migrasi dan factory Anda
        // Misalnya, jika migrasi menggunakan 'jenis_kelamin' dengan enum 'L'/'P',
        // dan factory menghasilkan 'Laki-laki'/'Perempuan', Anda mungkin perlu mutator
        // atau pastikan factory menghasilkan nilai yang sesuai dengan enum DB.
        // Untuk 'jenis_kelamin', jika enum di DB adalah 'L'/'P' dan factory menghasilkan 'Laki-laki'/'Perempuan',
        // maka perlu ada penyesuaian di factory atau menggunakan mutator di model ini.
        // Namun, jika migrasi terakhir Anda sudah menggunakan enum 'Laki-laki'/'Perempuan', maka ini sudah OK.
    ];

    // Relasi-relasi (sudah terlihat benar berdasarkan nama kolom foreign key)
    public function programStudi()
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id', 'id_prodi');
    }

    public function dosenPa()
    {
        return $this->belongsTo(Dosen::class, 'dosen_pa_id', 'id_dosen');
    }

    public function krs()
    {
        // Asumsi foreign key di tabel 'krs' adalah 'mahasiswa_id' yang merujuk ke 'nim' di tabel 'mahasiswa'
        return $this->hasMany(Krs::class, 'mahasiswa_id', 'nim');
    }

    public function ips()
    {
        return $this->hasMany(Ips::class, 'mahasiswa_id', 'nim');
    }

    public function ipk()
    {
        return $this->hasOne(Ipk::class, 'mahasiswa_id', 'nim');
    }

    public function absensi()
    {
        return $this->hasMany(Absensi::class, 'mahasiswa_id', 'nim');
    }
}