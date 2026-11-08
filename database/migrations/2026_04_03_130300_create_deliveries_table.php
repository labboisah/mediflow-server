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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('labour_id')->constrained('labours')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('delivered_by')->constrained('users');
            $table->foreignId('assisted_by')->constrained('users')->nullable();
            
            // Delivery details
            $table->dateTime('delivery_date_time');
            $table->string('delivery_type'); // vaginal, assisted_vaginal (vacuum/forceps), caesarean
            $table->text('reason_for_delivery_type')->nullable();
            
            // For assisted vaginal delivery
            $table->string('assisted_with')->nullable(); // vacuum, forceps
            $table->text('indication_for_assistance')->nullable();
            
            // For caesarean section
            $table->string('caesarean_type')->nullable(); // elective, emergency
            $table->text('indication_for_caesarean')->nullable();
            
            // Perineal trauma
            $table->string('perineal_trauma')->nullable(); // intact, 1st degree, 2nd degree, 3rd degree, 4th degree
            $table->text('episiotomy')->nullable();
            $table->text('perineal_repair')->nullable();
            
            // Third stage details
            $table->string('placenta_delivery_method')->nullable(); // spontaneous, manual removal
            $table->dateTime('placenta_delivered_at');
            $table->text('placental_examination')->nullable();
            
            // Maternal blood loss
            $table->string('estimated_blood_loss')->nullable();
            $table->text('blood_loss_assessment')->nullable();
            
            // Maternal condition after delivery
            $table->string('uterine_tone')->nullable();
            $table->string('per_vaginal_bleeding')->nullable();
            $table->string('blood_pressure')->nullable();
            $table->string('pulse_rate')->nullable();
            $table->string('general_condition')->nullable();
            
            // Complications during delivery
            $table->text('complications')->nullable();
            $table->text('management_of_complications')->nullable();
            
            // Baby details (will be expanded in Newborn table)
            $table->integer('number_of_babies')->default(1);
            
            // General notes
            $table->text('delivery_summary')->nullable();
            $table->enum('delivery_status', ['successful', 'complicated', 'maternal_death', 'fetal_death'])->default('successful');
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
