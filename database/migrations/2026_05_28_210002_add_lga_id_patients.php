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
        Schema::table('patient_demographics', function (Blueprint $table) {
            $table->foreignId('lga_id')->nullable()->constrained('lgas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_demographics', function (Blueprint $table) {
            $table->dropForeignKey(['lga_id']);
            $table->dropColumn('lga_id');
        });
    }
};
