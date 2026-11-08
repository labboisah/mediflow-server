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
        Schema::table('continuations', function (Blueprint $table) {
            if (! Schema::hasColumn('continuations', 'history')) {
                $table->text('history')->nullable();
            }
            if (! Schema::hasColumn('continuations', 'examination')) {
                $table->text('examination')->nullable();
            }
            if (! Schema::hasColumn('continuations', 'diagnose')) {
                $table->text('diagnose')->nullable();
            }
            if (! Schema::hasColumn('continuations', 'plan')) {
                $table->text('plan')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('continuations', function (Blueprint $table) {
            if (Schema::hasColumn('continuations', 'history')) {
                $table->dropColumn('history');
            }
            if (Schema::hasColumn('continuations', 'examination')) {
                $table->dropColumn('examination');
            }
            if (Schema::hasColumn('continuations', 'diagnose')) {
                $table->dropColumn('diagnose');
            }
            if (Schema::hasColumn('continuations', 'plan')) {
                $table->dropColumn('plan');
            }
        });
    }
};
