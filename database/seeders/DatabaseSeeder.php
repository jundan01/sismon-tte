<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Urutan seeder:
     * 1. Master SKPD
     * 2. Master Jabatan & Golongan
     * 3. Superadmin Users (ajriajundan@gmail.com, admin@cirebonkota.go.id, operator@cirebonkota.go.id)
     */
    public function run(): void
    {
        $this->call([
            SkpdSeeder::class,
            MasterJabatanGolonganSeeder::class,
            SuperadminUserSeeder::class,
        ]);
    }
}