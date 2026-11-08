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
        Schema::table('service_requests', function (Blueprint $table) {
            $table->string('payment_status')->default('pending')->after('status');
        });
        Schema::table('investigation_requests', function (Blueprint $table) {
            $table->string('payment_status')->default('pending')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
        Schema::table('investigation_requests', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
    }
};
