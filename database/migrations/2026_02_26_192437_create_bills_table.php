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
        if (! Schema::hasTable('bills')) {
            return;
        }

        Schema::table('bills', function (Blueprint $table) {
            if (! Schema::hasColumn('bills', 'bill_no')) {
                $table->string('bill_no')->nullable()->after('bill_number')->index();
            }

            if (! Schema::hasColumn('bills', 'patient_name')) {
                $table->string('patient_name')->nullable()->after('bill_no');
            }

            if (! Schema::hasColumn('bills', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->default(0)->after('amount');
            }

            if (! Schema::hasColumn('bills', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('total_amount');
            }

            if (! Schema::hasColumn('bills', 'final_amount')) {
                $table->decimal('final_amount', 12, 2)->default(0)->after('discount_amount');
            }

            if (! Schema::hasColumn('bills', 'total_paid')) {
                $table->decimal('total_paid', 12, 2)->default(0)->after('final_amount');
            }

            if (! Schema::hasColumn('bills', 'legacy_balance')) {
                $table->decimal('legacy_balance', 12, 2)->default(0)->after('total_paid');
            }

            if (! Schema::hasColumn('bills', 'payment_status')) {
                $table->string('payment_status')->default('unpaid')->after('status');
            }

            if (! Schema::hasColumn('bills', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('issued_by')->constrained()->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('bills')) {
            return;
        }

        Schema::table('bills', function (Blueprint $table) {
            if (Schema::hasColumn('bills', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }

            foreach (['bill_no', 'patient_name', 'total_amount', 'discount_amount', 'final_amount', 'total_paid', 'legacy_balance', 'payment_status'] as $column) {
                if (Schema::hasColumn('bills', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
