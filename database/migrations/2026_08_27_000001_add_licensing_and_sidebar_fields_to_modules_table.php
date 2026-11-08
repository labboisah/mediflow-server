<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            if (! Schema::hasColumn('modules', 'license_module')) {
                $table->string('license_module')->nullable()->after('group')->index();
            }

            if (! Schema::hasColumn('modules', 'sidebar_group')) {
                $table->string('sidebar_group')->nullable()->after('license_module')->index();
            }

            if (! Schema::hasColumn('modules', 'sidebar_patterns')) {
                $table->json('sidebar_patterns')->nullable()->after('sidebar_group');
            }

            if (! Schema::hasColumn('modules', 'is_sidebar_visible')) {
                $table->boolean('is_sidebar_visible')->default(true)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            if (Schema::hasColumn('modules', 'is_sidebar_visible')) {
                $table->dropColumn('is_sidebar_visible');
            }

            if (Schema::hasColumn('modules', 'sidebar_patterns')) {
                $table->dropColumn('sidebar_patterns');
            }

            if (Schema::hasColumn('modules', 'sidebar_group')) {
                $table->dropIndex(['sidebar_group']);
                $table->dropColumn('sidebar_group');
            }

            if (Schema::hasColumn('modules', 'license_module')) {
                $table->dropIndex(['license_module']);
                $table->dropColumn('license_module');
            }
        });
    }
};
