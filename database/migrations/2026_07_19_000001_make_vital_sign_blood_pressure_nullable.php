<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vital_signs', function (Blueprint $table) {
            $table->integer('blood_pressure_systolic')->nullable()->change();
            $table->integer('blood_pressure_diastolic')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vital_signs', function (Blueprint $table) {
            $table->integer('blood_pressure_systolic')->nullable(false)->change();
            $table->integer('blood_pressure_diastolic')->nullable(false)->change();
        });
    }
};
