<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->json('welcome_appearance')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('system_settings', fn (Blueprint $table) => $table->dropColumn('welcome_appearance'));
    }
};
