<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mahasiswa;
use App\Models\Ips;
use App\Models\Ipk;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class IpkIpsSeeder extends Seeder
{
    protected $letterGrades = [
        'A' => 4.00, 'B+' => 3.50, 'B' => 3.00, 'C+' => 2.50,
        'C' => 2.00, 'D+' => 1.50, 'D' => 1.00, 'E' => 0.00
    ];

    protected $batchSize = 500;

    public function run()
    {
        $this->command->info('🚀 Memulai seeding IPS dan IPK dengan optimasi...');

        // Clear existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('ips')->truncate();
        DB::table('ipk')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Process IPS first using raw SQL for speed
        $this->processIpsWithRawQuery();
        
        // Process IPK using aggregated data
        $this->processIpkWithRawQuery();

        $this->command->info('✅ Seeding selesai!');
        $this->showResults();
    }

    /**
     * Process IPS using optimized raw SQL
     */
    private function processIpsWithRawQuery()
    {
        $this->command->info('📊 Processing IPS data...');

        $query = "
            INSERT INTO ips (mahasiswa_id, tahun_akademik_id, ips, total_sks, created_at, updated_at)
            SELECT 
                k.mahasiswa_id,
                k.tahun_akademik_id,
                ROUND(
                    SUM(mk.sks * 
                        CASE na.nilai_huruf 
                            WHEN 'A' THEN 4.00
                            WHEN 'B+' THEN 3.50
                            WHEN 'B' THEN 3.00
                            WHEN 'C+' THEN 2.50
                            WHEN 'C' THEN 2.00
                            WHEN 'D+' THEN 1.50
                            WHEN 'D' THEN 1.00
                            WHEN 'E' THEN 0.00
                            ELSE 0.00
                        END
                    ) / SUM(mk.sks), 2
                ) as ips,
                SUM(mk.sks) as total_sks,
                NOW() as created_at,
                NOW() as updated_at
            FROM krs k
            INNER JOIN krs_detail kd ON k.id_krs = kd.krs_id
            INNER JOIN kelas kl ON kd.kelas_id = kl.id_kelas
            INNER JOIN mata_kuliah mk ON kl.mata_kuliah_id = mk.kode_matakuliah
            INNER JOIN nilai_akhir na ON kd.id_krsdetail = na.krs_detail_id
            WHERE na.nilai_huruf IN ('A', 'B+', 'B', 'C+', 'C', 'D+', 'D', 'E')
            GROUP BY k.mahasiswa_id, k.tahun_akademik_id
            HAVING SUM(mk.sks) > 0
        ";

        try {
            $affectedRows = DB::insert($query);
            $this->command->info("✅ IPS berhasil diproses");
        } catch (\Exception $e) {
            $this->command->error("❌ Error processing IPS: " . $e->getMessage());
            Log::error("IPS processing error: " . $e->getMessage());
        }
    }

    /**
     * Process IPK using optimized raw SQL
     */
    private function processIpkWithRawQuery()
    {
        $this->command->info('📊 Processing IPK data...');

        $query = "
            INSERT INTO ipk (mahasiswa_id, ipk, total_sks, created_at, updated_at)
            SELECT 
                k.mahasiswa_id,
                ROUND(
                    SUM(mk.sks * 
                        CASE na.nilai_huruf 
                            WHEN 'A' THEN 4.00
                            WHEN 'B+' THEN 3.50
                            WHEN 'B' THEN 3.00
                            WHEN 'C+' THEN 2.50
                            WHEN 'C' THEN 2.00
                            WHEN 'D+' THEN 1.50
                            WHEN 'D' THEN 1.00
                            WHEN 'E' THEN 0.00
                            ELSE 0.00
                        END
                    ) / SUM(mk.sks), 2
                ) as ipk,
                SUM(mk.sks) as total_sks,
                NOW() as created_at,
                NOW() as updated_at
            FROM krs k
            INNER JOIN krs_detail kd ON k.id_krs = kd.krs_id
            INNER JOIN kelas kl ON kd.kelas_id = kl.id_kelas
            INNER JOIN mata_kuliah mk ON kl.mata_kuliah_id = mk.kode_matakuliah
            INNER JOIN nilai_akhir na ON kd.id_krsdetail = na.krs_detail_id
            WHERE na.nilai_huruf IN ('A', 'B+', 'B', 'C+', 'C', 'D+', 'D', 'E')
            GROUP BY k.mahasiswa_id
            HAVING SUM(mk.sks) > 0
        ";

        try {
            $affectedRows = DB::insert($query);
            $this->command->info("✅ IPK berhasil diproses");
        } catch (\Exception $e) {
            $this->command->error("❌ Error processing IPK: " . $e->getMessage());
            Log::error("IPK processing error: " . $e->getMessage());
        }
    }

    /**
     * Get column names from mahasiswa table dynamically
     */
    private function getMahasiswaColumns()
    {
        $columns = Schema::getColumnListing('mahasiswa');
        
        // Try to find name column variations
        $nameColumn = 'nim'; // fallback to nim
        $possibleNameColumns = ['nama', 'name', 'nama_mahasiswa', 'nama_lengkap', 'full_name'];
        
        foreach ($possibleNameColumns as $col) {
            if (in_array($col, $columns)) {
                $nameColumn = $col;
                break;
            }
        }
        
        return (object) [
            'name' => $nameColumn,
            'nim' => 'nim',
            'all' => $columns
        ];
    }

    /**
     * Show results and verification
     */
    private function showResults()
    {
        $this->command->info("\n📊 HASIL SEEDING:");
        
        // Count records
        $ipsCount = DB::table('ips')->count();
        $ipkCount = DB::table('ipk')->count();
        $krsCount = DB::table('krs')->count();
        $mahasiswaCount = DB::table('mahasiswa')->count();
        
        $this->command->info("   📋 Total Mahasiswa: {$mahasiswaCount}");
        $this->command->info("   📋 Total KRS: {$krsCount}");
        $this->command->info("   📋 Total IPS: {$ipsCount}");
        $this->command->info("   📋 Total IPK: {$ipkCount}");
        
        // Show coverage
        $coverageIps = $krsCount > 0 ? round(($ipsCount / $krsCount) * 100, 2) : 0;
        $coverageIpk = $mahasiswaCount > 0 ? round(($ipkCount / $mahasiswaCount) * 100, 2) : 0;
        
        $this->command->info("   📈 Coverage IPS: {$coverageIps}% dari total KRS");
        $this->command->info("   📈 Coverage IPK: {$coverageIpk}% dari total Mahasiswa");
        
        // Show sample data
        $this->showSampleData();
        
        // Run verification
        $this->runVerification();
    }

    /**
     * Show sample data with dynamic column detection
     */
    private function showSampleData()
    {
        $this->command->info("\n🔍 SAMPLE DATA:");
        
        try {
            // Get mahasiswa column info
            $mahasiswaColumns = $this->getMahasiswaColumns();
            
            // Sample IPS with safe column selection
            $sampleIps = DB::table('ips')
                ->join('mahasiswa', 'ips.mahasiswa_id', '=', 'mahasiswa.nim')
                ->join('tahun_akademik', 'ips.tahun_akademik_id', '=', 'tahun_akademik.id_tahunakademik')
                ->select([
                    "mahasiswa.{$mahasiswaColumns->name} as nama_mahasiswa",
                    'mahasiswa.nim', 
                    'tahun_akademik.tahun_akademik', 
                    'tahun_akademik.semester', 
                    'ips.ips', 
                    'ips.total_sks'
                ])
                ->orderBy('ips.ips', 'desc')
                ->first();
                
            if ($sampleIps) {
                $this->command->info("   📋 Sample IPS Tertinggi:");
                $this->command->info("      - {$sampleIps->nama_mahasiswa} ({$sampleIps->nim})");
                $this->command->info("      - {$sampleIps->tahun_akademik} {$sampleIps->semester}");
                $this->command->info("      - IPS: {$sampleIps->ips} | SKS: {$sampleIps->total_sks}");
            }
            
            // Sample IPK with safe column selection
            $sampleIpk = DB::table('ipk')
                ->join('mahasiswa', 'ipk.mahasiswa_id', '=', 'mahasiswa.nim')
                ->select([
                    "mahasiswa.{$mahasiswaColumns->name} as nama_mahasiswa",
                    'mahasiswa.nim', 
                    'ipk.ipk', 
                    'ipk.total_sks'
                ])
                ->orderBy('ipk.ipk', 'desc')
                ->first();
                
            if ($sampleIpk) {
                $this->command->info("   📋 Sample IPK Tertinggi:");
                $this->command->info("      - {$sampleIpk->nama_mahasiswa} ({$sampleIpk->nim})");
                $this->command->info("      - IPK: {$sampleIpk->ipk} | Total SKS: {$sampleIpk->total_sks}");
            }
            
        } catch (\Exception $e) {
            $this->command->warn("⚠  Error showing sample data: " . $e->getMessage());
            $this->command->info("   📋 Showing basic data instead...");
            
            // Fallback: show data without joins
            $basicIps = DB::table('ips')
                ->select('mahasiswa_id', 'ips', 'total_sks')
                ->orderBy('ips', 'desc')
                ->first();
                
            $basicIpk = DB::table('ipk')
                ->select('mahasiswa_id', 'ipk', 'total_sks')
                ->orderBy('ipk', 'desc')
                ->first();
                
            if ($basicIps) {
                $this->command->info("   📋 Sample IPS Tertinggi:");
                $this->command->info("      - Mahasiswa ID: {$basicIps->mahasiswa_id}");
                $this->command->info("      - IPS: {$basicIps->ips} | SKS: {$basicIps->total_sks}");
            }
            
            if ($basicIpk) {
                $this->command->info("   📋 Sample IPK Tertinggi:");
                $this->command->info("      - Mahasiswa ID: {$basicIpk->mahasiswa_id}");
                $this->command->info("      - IPK: {$basicIpk->ipk} | Total SKS: {$basicIpk->total_sks}");
            }
        }
    }

    /**
     * Run verification queries
     */
    private function runVerification()
    {
        $this->command->info("\n🔍 VERIFIKASI DATA:");
        
        try {
            // Check for missing data
            $mahasiswaWithoutIpk = DB::table('mahasiswa')
                ->leftJoin('ipk', 'mahasiswa.nim', '=', 'ipk.mahasiswa_id')
                ->whereNull('ipk.mahasiswa_id')
                ->count();
                
            $krsWithoutIps = DB::table('krs')
                ->leftJoin('ips', function($join) {
                    $join->on('krs.mahasiswa_id', '=', 'ips.mahasiswa_id')
                         ->on('krs.tahun_akademik_id', '=', 'ips.tahun_akademik_id');
                })
                ->whereNull('ips.mahasiswa_id')
                ->count();
                
            $this->command->info("   ⚠  Mahasiswa tanpa IPK: {$mahasiswaWithoutIpk}");
            $this->command->info("   ⚠  KRS tanpa IPS: {$krsWithoutIps}");
            
            // Check data integrity
            $avgIps = DB::table('ips')->avg('ips');
            $avgIpk = DB::table('ipk')->avg('ipk');
            $maxIps = DB::table('ips')->max('ips');
            $maxIpk = DB::table('ipk')->max('ipk');
            $minIps = DB::table('ips')->min('ips');
            $minIpk = DB::table('ipk')->min('ipk');
            
            $this->command->info("\n📊 STATISTIK NILAI:");
            $this->command->info("   📈 IPS - Min: {$minIps} | Max: {$maxIps} | Avg: " . round($avgIps, 2));
            $this->command->info("   📈 IPK - Min: {$minIpk} | Max: {$maxIpk} | Avg: " . round($avgIpk, 2));
            
            // Check for suspicious data
            $highIps = DB::table('ips')->where('ips', '>', 4.00)->count();
            $highIpk = DB::table('ipk')->where('ipk', '>', 4.00)->count();
            $zeroIps = DB::table('ips')->where('ips', '=', 0)->count();
            $zeroIpk = DB::table('ipk')->where('ipk', '=', 0)->count();
            
            if ($highIps > 0 || $highIpk > 0) {
                $this->command->warn("   ⚠  Data mencurigakan - IPS > 4.0: {$highIps}, IPK > 4.0: {$highIpk}");
            }
            
            if ($zeroIps > 0 || $zeroIpk > 0) {
                $this->command->warn("   ⚠  Nilai nol - IPS = 0: {$zeroIps}, IPK = 0: {$zeroIpk}");
            }
            
            // Show reasons for missing data
            if ($krsWithoutIps > 0) {
                $this->checkMissingIpsReasons();
            }
            
        } catch (\Exception $e) {
            $this->command->error("❌ Error in verification: " . $e->getMessage());
            Log::error("Verification error: " . $e->getMessage());
        }
    }

    /**
     * Check reasons for missing IPS data
     */
    private function checkMissingIpsReasons()
    {
        $this->command->info("\n🔍 ANALISIS DATA HILANG:");
        
        try {
            // KRS without details
            $krsWithoutDetails = DB::table('krs')
                ->leftJoin('krs_detail', 'krs.id_krs', '=', 'krs_detail.krs_id')
                ->whereNull('krs_detail.krs_id')
                ->count();
                
            // KRS details without grades
            $detailsWithoutGrades = DB::table('krs_detail')
                ->leftJoin('nilai_akhir', 'krs_detail.id_krsdetail', '=', 'nilai_akhir.krs_detail_id')
                ->whereNull('nilai_akhir.krs_detail_id')
                ->count();
                
            // KRS details without classes
            $detailsWithoutClasses = DB::table('krs_detail')
                ->leftJoin('kelas', 'krs_detail.kelas_id', '=', 'kelas.id_kelas')
                ->whereNull('kelas.id_kelas')
                ->count();
                
            // Classes without courses
            $classesWithoutCourses = DB::table('kelas')
                ->leftJoin('mata_kuliah', 'kelas.mata_kuliah_id', '=', 'mata_kuliah.kode_matakuliah')
                ->whereNull('mata_kuliah.kode_matakuliah')
                ->count();
                
            $this->command->info("   📋 KRS tanpa detail: {$krsWithoutDetails}");
            $this->command->info("   📋 Detail tanpa nilai: {$detailsWithoutGrades}");
            $this->command->info("   📋 Detail tanpa kelas: {$detailsWithoutClasses}");
            $this->command->info("   📋 Kelas tanpa mata kuliah: {$classesWithoutCourses}");
            
            // Show grade distribution
            $gradeDistribution = DB::table('nilai_akhir')
                ->select('nilai_huruf', DB::raw('COUNT(*) as jumlah'))
                ->groupBy('nilai_huruf')
                ->orderBy('jumlah', 'desc')
                ->limit(10) // Limit to prevent overwhelming output
                ->get();
                
            $this->command->info("\n📊 DISTRIBUSI NILAI (Top 10):");
            foreach ($gradeDistribution as $grade) {
                $this->command->info("   📋 {$grade->nilai_huruf}: {$grade->jumlah}");
            }
            
        } catch (\Exception $e) {
            $this->command->error("❌ Error in missing data analysis: " . $e->getMessage());
            Log::error("Missing data analysis error: " . $e->getMessage());
        }
    }
}