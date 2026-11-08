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
        Schema::table('bills', function (Blueprint $table) {
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('due_amount', 12, 2)->default(0);
        });

        App\Models\PaymentMethod::firstOrCreate([
            'name'=>'Student'
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropColumn('discount');
            $table->dropColumn('due_amount');
        });
    }
};
