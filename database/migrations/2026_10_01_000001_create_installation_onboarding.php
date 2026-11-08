<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installation_onboarding', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->string('license_identifier')->nullable();
        });
        DB::table('installation_onboarding')->insert(['id' => 1]);
    }

    public function down(): void
    {
        Schema::dropIfExists('installation_onboarding');
    }
};
