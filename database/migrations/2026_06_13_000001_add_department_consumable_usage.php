<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            if (! Schema::hasColumn('consumables', 'current_quantity')) {
                $table->decimal('current_quantity', 12, 2)->default(0)->after('reorder_level');
            }
        });

        Schema::create('consumable_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consumable_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->date('usage_date');
            $table->string('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::table('consumables')
            ->leftJoin('consumable_stocks', 'consumables.id', '=', 'consumable_stocks.consumable_id')
            ->select('consumables.id', DB::raw('COALESCE(SUM(consumable_stocks.quantity), 0) as total_quantity'))
            ->groupBy('consumables.id')
            ->orderBy('consumables.id')
            ->get()
            ->each(function ($row) {
                DB::table('consumables')
                    ->where('id', $row->id)
                    ->update(['current_quantity' => $row->total_quantity]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('consumable_usages');

        Schema::table('consumables', function (Blueprint $table) {
            if (Schema::hasColumn('consumables', 'current_quantity')) {
                $table->dropColumn('current_quantity');
            }
        });
    }
};
