<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migrasi tabel `users` — IAM Superadmin dengan TOTP MFA.
     * Ref: SCHEMA.md §7.3
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('password', 255);
            $table->text('mfa_secret')->nullable();
            $table->boolean('is_mfa_enabled')->default(false);
            $table->string('role', 30)->default('superadmin');
            $table->timestampTz('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        // CHECK constraint: hanya role 'superadmin'
        \Illuminate\Support\Facades\DB::statement("
            ALTER TABLE users
            ADD CONSTRAINT chk_role CHECK (role = 'superadmin')
        ");

        // Tabel sessions untuk session driver database
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
