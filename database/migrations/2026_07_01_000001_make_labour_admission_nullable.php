<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('labours', 'admission_id')) {
            return;
        }

        Schema::table('labours', function (Blueprint $table) {
            $table->dropForeign(['admission_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `labours` MODIFY `admission_id` BIGINT UNSIGNED NULL');
        }

        Schema::table('labours', function (Blueprint $table) {
            $table->foreign('admission_id')->references('id')->on('admissions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('labours', 'admission_id')) {
            return;
        }

        Schema::table('labours', function (Blueprint $table) {
            $table->dropForeign(['admission_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `labours` MODIFY `admission_id` BIGINT UNSIGNED NOT NULL');
        }

        Schema::table('labours', function (Blueprint $table) {
            $table->foreign('admission_id')->references('id')->on('admissions')->cascadeOnDelete();
        });
    }
};
