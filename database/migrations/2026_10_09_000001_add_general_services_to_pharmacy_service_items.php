<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_service_items', function (Blueprint $table) {
            $table->dropForeign(['pharmacy_service_id']);
        });

        Schema::table('pharmacy_service_items', function (Blueprint $table) {
            $table->foreignId('pharmacy_service_id')->nullable()->change();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_service_items', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
        });
    }
};