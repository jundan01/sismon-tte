<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Menambahkan kolom UUID pada tabel utama untuk parameter identifier URL publik & admin.
     * Mencegah serangan enumerasi ID (OWASP Insecure Direct Object References - IDOR).
     */
    public function up(): void
    {
        $tables = [
            'skpd',
            'master_jabatan_golongan',
            'users',
            'sertifikat_tte',
            'pendaftaran_tte',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasColumn($table, 'uuid')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->uuid('uuid')->nullable()->after('id');
                });

                // Isi data yang sudah ada dengan UUID v4
                $records = DB::table($table)->select('id')->get();
                foreach ($records as $record) {
                    DB::table($table)->where('id', $record->id)->update([
                        'uuid' => (string) Str::uuid(),
                    ]);
                }

                // Ubah menjadi NOT NULL dan buat UNIQUE index
                Schema::table($table, function (Blueprint $t) use ($table) {
                    $t->uuid('uuid')->nullable(false)->change();
                    $t->unique('uuid', "idx_{$table}_uuid_unique");
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'skpd',
            'master_jabatan_golongan',
            'users',
            'sertifikat_tte',
            'pendaftaran_tte',
        ];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'uuid')) {
                Schema::table($table, function (Blueprint $t) use ($table) {
                    $t->dropUnique("idx_{$table}_uuid_unique");
                    $t->dropColumn('uuid');
                });
            }
        }
    }
};
