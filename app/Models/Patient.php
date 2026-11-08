<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use SoftDeletes, Syncable;

    protected $guarded = [];

    protected $casts = [
        'registration_date' => 'datetime',
        'is_walkIn' => 'boolean',
    ];

    /**
     * Get the demographic information for this patient
     */
    public function demographic()
    {
        return $this->hasOne(PatientDemographic::class);
    }

    public function patientDemographic()
    {
        return $this->hasOne(PatientDemographic::class);
    }

    

    public function patientAdmissions() {
        return $this->hasMany(PatientAdmission::class);
    }

   

    public function fileType() {
        return $this->belongsTo(FileType::class);
    }

    public function age() {
        return \Carbon\Carbon::parse($this->demographic->date_of_birth)->age;
    }

    public function name() {
        return $this->demographic->first_name . ' ' . $this->demographic->last_name;
    }

    /**
     * Get all visits for this patient
     */
    public function visits()
    {
        return $this->hasMany(PatientVisit::class);
    }

    public function abscondedAdmissions()
    {
        return $this->patientVisits
            ->flatMap(fn ($visit) => $visit->admissions)
            ->where('status', 'absconded')
            ->values();
    }

    public function samaAdmissions()
    {
        return $this->patientVisits
            ->flatMap(fn ($visit) => $visit->admissions)
            ->where('status', 'sama')
            ->values();
    }

    public function generateFileOpeningBill($visit, $discount = 0, $isAnc = false)
    {
        if ($isAnc) {
            $consultationCharges = 500; // Example: additional 20% discount for ANC patients
            $consultationService = Service::find(17);
        }else{
            $consultationCharges = 1000; // Standard consultation fee
            $consultationService = Service::find(6);
        }

        // create bill for file opening with discount
        $service = Service::find(1);

        $bill = new Bill();
        $bill->patient_visit_id = $visit->id;
        $baseAmount = $this->fileType->price; // Example amount for file opening
        $discountAmount = $baseAmount * ($discount / 100);
        $bill->amount = $baseAmount;
        $bill->due_amount = max(0, $baseAmount - $discountAmount);
        $bill->discount = $discountAmount;
        $bill->service_description = 'File Opening Fee & Consultation';
        $bill->bill_number = Bill::generateBillNumber();
        $bill->issued_by = auth()->user()->id;
        $bill->issued_date= now();
        $bill->due_date = now()->addDays(7); // Due in 7 days
        $bill->status = 'pending';
        $bill->notes = 'File Opening';
        $bill->save();

        $bill->billServices()->create([
            'service_id'=>$service->id,
            'unit_price'=>$bill->due_amount,
            'quantity'=> 1,
            'subtotal' => $bill->due_amount
            ]);

        $bill->billServices()->create([
            'service_id'=>$consultationService->id,
            'unit_price'=>$consultationCharges,
            'quantity'=> 1,
            'subtotal' => $consultationCharges
        ]);

        // send  consultation service request
        $serviceRequest = new ServiceRequest();
        $serviceRequest->patient_visit_id = $visit->id;
        $serviceRequest->service_id = $consultationService->id;
        $serviceRequest->requested_by = auth()->user()->id;
        $serviceRequest->requested_at = now();
        $serviceRequest->status = 'pending';
        $serviceRequest->clinical_diagnoses = 'File opening & consultation';
        $serviceRequest->save();
        
    }

    public function registerNewVisit() {

        
        $visit = $this->patientVisits()->create([
            'visit_date'=>date('d M, Y'),
            'visit_type'=>$service->name ?? 'Consultation',
            'flag'=>$service->category ?? 'Normal',
            'created_by'=>auth()->user()->id
        ]);

        

        return $visit;
    }

    

    /**
     * Get appointments for this patient
     */
    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Get referrals for this patient
     */
    public function referrals()
    {
        return $this->hasMany(PatientReferral::class);
    }

    /**
     * Get next of kin information
     */
    public function nextOfKin()
    {
        return $this->hasOne(NextOfKin::class);
    }

    public function patientVisits() {
        return $this->hasMany(PatientVisit::class);
    }

    public function continuations()
    {
        return $this->hasManyThrough(Continuation::class, PatientVisit::class);
    }

    /**
     * Get all antenatal care records for this patient
     */
    public function antenatalCares()
    {
        return $this->hasMany(AntenatalCare::class);
    }

    /**
     * Get all labour records for this patient
     */
    public function labours()
    {
        return $this->hasMany(Labour::class);
    }

    /**
     * Get all deliveries for this patient
     */
    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * Get all newborns from this patient (as mother)
     */
    public function newborns()
    {
        return $this->hasMany(Newborn::class);
    }

    /**
     * Get all newborn examinations from this patient's newborns.
     */
    public function newbornExaminations()
    {
        return $this->hasManyThrough(NewbornExamination::class, Newborn::class, 'patient_id', 'newborn_id', 'id', 'id');
    }

    /**
     * Get all postnatal examinations for this patient
     */
    public function postnatalExaminations()
    {
        return $this->hasMany(PostnatalExamination::class);
    }

    /**
     * Get all child follow-ups related to this patient (as mother)
     */
    public function childFollowUps()
    {
        return $this->hasMany(ChildFollowUp::class);
    }

    /**
     * Get the latest antenatal care record
     */
    public function latestAntenatalCare()
    {
        return $this->antenatalCares()->latest('created_at')->first();
    }

    /**
     * Get the latest labour
     */
    public function latestLabour()
    {
        return $this->labours()->latest('labour_onset_time')->first();
    }

    /**
     * Get the latest delivery
     */
    public function latestDelivery()
    {
        return $this->deliveries()->latest('delivery_date_time')->first();
    }

   
    public function recordFirstVisit() {

        $visit = $this->patientVisits()->create([
            'visit_date'=>date('d M, Y'),
            'visit_type'=>'Consultation',
            'created_by'=>auth()->user()->id
        ]);

        $visit->generateFileOpeningBill();

        

    }

    // currnt visit
    public function currentVisit() { 
        return $this->patientVisits()->latest('created_at')->first(); 
    }
    /**
     * Generate unique hospital number
     */
    public static function generateHospitalNumber()
    {
        $year = substr(date('Y'), 2, 2);
        $count = static::count() + 1;
        return 'FYH' . $year . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get payment summary for this patient
     */
    public function payment()
    {
        $total = 0;
        $count = 0;
        $paid = 0;
        $reversed = 0;

        foreach ($this->patientVisits as $visit) {
            foreach ($visit->bills as $bill) {
                foreach ($bill->payments as $payment) {
                    if ($payment->status === 'completed') {
                        $paid += $payment->amount;
                        $count++;
                    }
                }
                $total += $bill->amount;
            }
        }

        return [
            'total' => $total,
            'count' => $count,
            'paid' => $paid,
            'pending' => $total - $paid,
            'outstanding' => $total - $paid,
            'revenue' => $paid,
            'reversed' => $reversed // Placeholder for any reversed payments logic
        ];
    }

     public function bills()
    {
        $amount = 0;
        $paid = 0;
        $pending = 0;
        $overdue = 0;
        $partial = 0;
        

        foreach ($this->patientVisits as $visit) {
            foreach ($visit->bills as $bill) {
                $amount+=$bill->amount;

                if($bill->status == 'paid'){
                    $paid += $bill->amount;
                }elseif($bill->status == 'pending'){
                    $pending += $bill->amount;
                    if(time() > strtotime($bill->due_date)){
                        $overdue+=$bill->getBalanceAttribute();
                    }
                }elseif($bill->status == 'partial'){
                    $partial += $bill->totalPaid();
                    if(time() > strtotime($bill->due_date)){
                        $overdue+=$bill->getBalanceAttribute();
                    }
                }

            }
        }

        return [
            'amount' => $amount,
            'paid' => $paid,
            'partial' => $partial,
            'pending' => $pending,
            'overdue' => $overdue,
            
        ];
    }

    /**
     * Search patient by hospital number, payment ID, or phone number
     */
    public static function search($query)
    {
        return static::whereHas('demographic', function ($q) use ($query) {
            $q->where('phone_number', 'like', "%{$query}%");
        })
        ->orWhere('hospital_number', 'like', "%{$query}%")
        ->orWhere('name', 'like', "%{$query}%")
        ->get();
    }

    /**
     * Check if patient already exists (prevent duplicates)
     */
    public static function checkDuplicate($email = null, $phoneNumber = null)
    {
        if ($email) {
            $exists = PatientDemographic::where('email', $email)->exists();
            if ($exists) return true;
        }

        if ($phoneNumber) {
            $exists = PatientDemographic::where('phone_number', $phoneNumber)->exists();
            if ($exists) return true;
        }

        return false;
    }
}
