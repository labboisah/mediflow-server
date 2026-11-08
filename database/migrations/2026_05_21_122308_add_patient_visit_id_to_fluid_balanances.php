<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fluid_balances', function (Blueprint $table) {
            $table->foreignId('patient_visit_id')->after('id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fluid_balances', function (Blueprint $table) {
            $table->dropForeign(['patient_visit_id']);
            $table->dropColumn('patient_visit_id');
        });
    }
};
