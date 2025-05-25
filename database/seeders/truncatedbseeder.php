<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TruncateDbSeeder extends Seeder 
{
    public function run()
    {
        $krsNilaiTables = [
            'nilai_akhir',
            'nilai',
            'krs_detail',
            'krs',
        ];

        $optionalTables = [
            'komponen_nilai',
        ];

        $this->command->warn('PERHATIAN: Proses ini akan menghapus data dari tabel-tabel yang dipilih!');
        
        $truncateKrsNilai = $this->command->confirm("Apakah Anda ingin menghapus semua data dari tabel: " . implode(', ', $krsNilaiTables) . "?", true);

        $tablesToTruncate = [];

        if ($truncateKrsNilai) {
            $tablesToTruncate = array_merge($tablesToTruncate, $krsNilaiTables);
        }

        foreach ($optionalTables as $table) {
            if ($this->command->confirm("Truncate juga tabel '{$table}' (potensi data bersama)?", false)) {
                $tablesToTruncate[] = $table;
            }
        }

        if (empty($tablesToTruncate)) {
            $this->command->info('Tidak ada tabel yang dipilih untuk di-truncate. Proses dibatalkan.');
            return;
        }

        $this->command->warn("Tabel berikut akan di-TRUNCATE: " . implode(', ', $tablesToTruncate));
        if ($this->command->confirm('Apakah Anda yakin ingin melanjutkan dengan daftar tabel ini?', false)) {
            Schema::disableForeignKeyConstraints();

            foreach ($tablesToTruncate as $table) {
                DB::table($table)->truncate();
                $this->command->info("Tabel '{$table}' berhasil di-truncate.");
            }

            Schema::enableForeignKeyConstraints();
            $this->command->info('Proses truncate selesai.');
        } else {
            $this->command->info('Proses truncate dibatalkan oleh pengguna.');
        }
    }
}