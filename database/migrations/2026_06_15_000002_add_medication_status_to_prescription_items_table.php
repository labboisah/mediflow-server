<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->string('medication_status')->default('started')->after('duration');
            $table->timestamp('medication_status_changed_at')->nullable()->after('medication_status');
            $table->foreignId('medication_status_changed_by')->nullable()->after('medication_status_changed_at');
        });
    }

    public function down(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->dropColumn([
                'medication_status',
                'medication_status_changed_at',
                'medication_status_changed_by',
            ]);
        });
    }
};
