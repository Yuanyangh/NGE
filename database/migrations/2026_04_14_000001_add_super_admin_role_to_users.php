<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL: alter the ENUM column to add 'super_admin'
        // SQLite (used in tests) does not support ENUM — the column is stored as TEXT there,
        // so no DDL change is needed for SQLite.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('customer','affiliate','admin','super_admin') NOT NULL DEFAULT 'customer'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('customer','affiliate','admin') NOT NULL DEFAULT 'customer'");
        }
    }
};
