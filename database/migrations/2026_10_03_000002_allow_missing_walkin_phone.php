<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('walkin_patients', function (Blueprint $table) {
            $table->string('phone_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('walkin_patients')->whereNull('phone_number')->exists()) {
            throw new RuntimeException('Cannot require walk-in phone numbers while imported walk-in records have no phone. Resolve those records before rolling back.');
        }

        Schema::table('walkin_patients', function (Blueprint $table) {
            $table->string('phone_number')->nullable(false)->change();
        });
    }
};
