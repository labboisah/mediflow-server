<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('temporary_permissions')) {
            return;
        }

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        // For MySQL / MariaDB: alter column to allow NULL
        if (in_array($driver, ['mysql', 'mysqli', 'mariadb'])) {
            DB::statement('ALTER TABLE `temporary_permissions` MODIFY `granted_by` BIGINT UNSIGNED NULL');
        } else {
            // Fallback: attempt to use schema change (requires doctrine/dbal)
            try {
                Schema::table('temporary_permissions', function (Blueprint $table) {
                    $table->unsignedBigInteger('granted_by')->nullable()->change();
                });
            } catch (\Throwable $e) {
                // If change isn't available, do nothing; developer should install doctrine/dbal or adjust manually.
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('temporary_permissions')) {
            return;
        }

        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if (in_array($driver, ['mysql', 'mysqli', 'mariadb'])) {
            DB::statement('ALTER TABLE `temporary_permissions` MODIFY `granted_by` BIGINT UNSIGNED NOT NULL');
        } else {
            try {
                Schema::table('temporary_permissions', function (Blueprint $table) {
                    $table->unsignedBigInteger('granted_by')->nullable(false)->change();
                });
            } catch (\Throwable $e) {
                // no-op
            }
        }
    }
};
