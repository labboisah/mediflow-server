<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_licenses') || Schema::hasColumn('client_licenses', 'branch_limit')) {
            return;
        }

        Schema::table('client_licenses', function (Blueprint $table) {
            $table->unsignedInteger('branch_limit')->nullable()->after('license_key');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('client_licenses') || ! Schema::hasColumn('client_licenses', 'branch_limit')) {
            return;
        }

        Schema::table('client_licenses', function (Blueprint $table) {
            $table->dropColumn('branch_limit');
        });
    }
};
