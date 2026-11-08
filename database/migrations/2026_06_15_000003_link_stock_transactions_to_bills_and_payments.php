<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_transactions', 'bill_id')) {
                $table->foreignId('bill_id')->nullable()->after('reference')->constrained('bills')->nullOnDelete();
            }

            if (! Schema::hasColumn('stock_transactions', 'payment_id')) {
                $table->foreignId('payment_id')->nullable()->after('bill_id')->constrained('payments')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('stock_transactions', 'payment_id')) {
                $table->dropConstrainedForeignId('payment_id');
            }

            if (Schema::hasColumn('stock_transactions', 'bill_id')) {
                $table->dropConstrainedForeignId('bill_id');
            }
        });
    }
};
