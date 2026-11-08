<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_results', function (Blueprint $table) {
            $table->foreignId('performed_by')->nullable()->after('staff_id')->constrained('users')->nullOnDelete();
            $table->foreignId('reported_by')->nullable()->after('performed_by')->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at')->nullable()->after('reported_by');
            $table->timestamp('reported_at')->nullable()->after('performed_at');
        });

        DB::table('bill_results')->update([
            'reported_by' => DB::raw('staff_id'),
            'reported_at' => DB::raw('completed_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('bill_results', function (Blueprint $table) {
            $table->dropConstrainedForeignId('performed_by');
            $table->dropConstrainedForeignId('reported_by');
            $table->dropColumn(['performed_at', 'reported_at']);
        });
    }
};
