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
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $categories = [
            'Personnel Cost', 
            'Laboratory Consumables',
            'Radiology Consumables',
            'Medical Stationaries/Data tools',
            'Nursing services consumables',
            'Other consumables',
            'Printing and Stationaries',
            'Fueling of Generators',
            'Fueling of Vehicles',
            'Cleaning Materials',
            'Utility Expenses',
            'Professional Charges',
            'Maintenance of Buildings',
            'Maintenance of Vehicles',
            'Maintenance of Machines and Equipment',
            'Meeting Expenses',
            'Advertising and publicity',
            'Regulatory Bodies charges and Taxes',
            'Staff Training and Development',
            'Transport and Travelling Expenses',
            'Staff welfare',
            'Entertainment and hospitality',
            'Provision for Depreciation',
            'Payment for Rent',
            'Patient welfare Support',
            'NHIA services',
            'Contribution to Foundation',
            'Debt services',
            'Refund to Patient',
            'Standing Imprest',
            'Software maintenance and repairs',
            'Staff Uniform and Bed Sheets'
        ];
        foreach($categories as $category){
            App\Models\ExpenseCategory::firstOrCreate(['name'=>$category]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
