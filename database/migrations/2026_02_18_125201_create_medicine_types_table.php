<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\MedicineType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medicine_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        $medicines = [

            "Tablet" => [
                "Paracetamol",
                "Ibuprofen",
                "Metformin",
                "Amlodipine",
                "Ciprofloxacin"
            ],

            "Capsule" => [
                "Amoxicillin",
                "Doxycycline",
                "Fluconazole",
                "Vitamin E",
                "Omeprazole"
            ],

            "Syrup" => [
                "Paracetamol Syrup",
                "Cough Syrup",
                "Lactulose Syrup",
                "Multivitamin Syrup",
                "Iron Syrup"
            ],

            "Injection" => [
                "Insulin",
                "Ceftriaxone",
                "Diclofenac Injection",
                "Vitamin B12 Injection",
                "Adrenaline"
            ],

            "Suspension" => [
                "Amoxicillin Suspension",
                "Azithromycin Suspension",
                "Antacid Suspension",
                "Albendazole Suspension"
            ],

            "Cream/Ointment" => [
                "Hydrocortisone Cream",
                "Clotrimazole Cream",
                "Gentamicin Ointment",
                "Diclofenac Gel"
            ],

            "Drops" => [
                "Eye Drops",
                "Ear Drops",
                "Nasal Drops"
            ],

            "Inhaler" => [
                "Salbutamol Inhaler",
                "Fluticasone Inhaler"
            ]

        ];
        foreach($medicines as $type => $meds){
            $medType = MedicineType::firstOrCreate(['name'=>$type]);
            foreach($meds as $med){
                $medType->medicines()->firstOrCreate(['name'=>$med]);
            }
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_types');
    }
};
