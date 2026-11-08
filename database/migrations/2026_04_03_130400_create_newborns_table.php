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
        Schema::create('newborns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_id')->constrained('deliveries')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade'); // mother
            $table->foreignId('recorded_by')->constrained('users');
            
            // Newborn identifiers
            $table->string('sex'); // male, female
            $table->integer('birth_order')->nullable(); // 1 for first baby, 2 for second, etc.
            $table->string('newborn_registration_number')->unique()->nullable();
            
            // Birth details
            $table->dateTime('birth_date_time');
            $table->string('birth_weight')->nullable(); // in grams
            $table->string('birth_length')->nullable(); // in cm
            $table->string('head_circumference')->nullable(); // in cm
            
            // Presentation and delivery
            $table->string('presentation')->nullable(); // cephalic, breech, etc.
            $table->text('delivery_notes')->nullable();
            
            // APGAR scores (recorded at 1 min and 5 min)
            $table->integer('apgar_score_1_minute')->nullable();
            $table->integer('apgar_score_5_minutes')->nullable();
            $table->integer('apgar_score_10_minutes')->nullable();
            
            // APGAR components (at 1 minute)
            $table->integer('apgar_appearance_1min')->nullable(); // 0-2
            $table->integer('apgar_pulse_1min')->nullable(); // 0-2
            $table->integer('apgar_grimace_1min')->nullable(); // 0-2
            $table->integer('apgar_activity_1min')->nullable(); // 0-2
            $table->integer('apgar_respiration_1min')->nullable(); // 0-2
            
            // Newborn condition
            $table->string('general_condition')->nullable();
            $table->text('physical_examination')->nullable();
            $table->text('birth_defects_noted')->nullable();
            $table->text('meconium_aspiration')->nullable();
            
            // Feeding and care
            $table->boolean('breastfeeding_initiated')->default(false);
            $table->dateTime('first_breastfeed_time')->nullable();
            $table->text('feeding_problems')->nullable();
            
            // Early newborn care
            $table->boolean('vitamin_k_given')->default(false);
            $table->boolean('eye_prophylaxis_given')->default(false);
            $table->boolean('immunizations_given')->default(false);
            $table->text('immunizations_details')->nullable();
            
            // Screening tests
            $table->boolean('screening_test_done')->default(false);
            $table->text('screening_test_results')->nullable();
            
            // Special interventions if required
            $table->text('special_care_needed')->nullable();
            $table->text('referred_to')->nullable();
            
            // Status and outcome
            $table->enum('status', ['alive', 'stillborn', 'early_neonatal_death'])->default('alive');
            $table->text('neonatal_observations')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('newborns');
    }
};
