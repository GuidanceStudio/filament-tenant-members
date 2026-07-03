<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Test users table. Mirrors the package's key strategy: uuid primary key when
 * key_type=uuid, auto-incrementing bigint otherwise. The filename sorts BEFORE the
 * package migrations (0001_01_02_*), so this table exists before the package's
 * foreign keys and its add_role_column_to_users_table migration run.
 */
return new class extends Migration
{
    public function up(): void
    {
        $uuid = config('filament-tenant-members.key_type', 'id') === 'uuid';

        Schema::create('users', function (Blueprint $table) use ($uuid) {
            $uuid ? $table->uuid('id')->primary() : $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
