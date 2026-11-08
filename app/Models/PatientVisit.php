<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientVisit extends Model
{
    use SoftDeletes, Syncable;

    protected $fillable = [
        'specialist_origin',
        'patient_id',
        'visit_date',
        'visit_type',
        'reason_for_visit',
        'clinical_notes',
        'status',
        'created_by',
    ];

    protected $casts = [
        'visit_date' => 'datetime',
    ];

    /**
     * Get the patient this visit belongs to
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function syncRelations(): array
    {
        return [
            'patient' => 'patient_sync_uuid',
        ];
    }

    public function syncDependencies(): array
    {
        return [
            'patient_sync_uuid' => Patient::class,
        ];
    }

    public function bills() {
        return $this->hasMany(Bill::class);
    }

    public function payments() {
        return $this->hasMany(Payment::class);
    }

    public function vitalSigns() {
        return $this->hasMany(VitalSign::class);
    }

    public function investigationRequests() {
        return $this->hasMany(InvestigationRequest::class);
    }

    public function serviceRequests() {
        return $this->hasMany(ServiceRequest::class);
    }

    /**
     * Get the user who created this visit record
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function admissions() {
        return $this->hasMany(Admission::class);
    }

    public function prescriptions(){
        return $this->hasMany(Prescription::Class);
    }

    public function continuations(){
        return $this->hasMany(Continuation::Class);
    }

    // get or create admission for this visit

    public function currentAdmission(Ward $ward = null) {
        $admission = null;
        foreach($this->admissions->whereIn('status',['registered','confirmed']) as $adm){
            $admission = $adm;
        }

        if(!$admission){
            $bed = $ward?->getAvailableBed();
            $admission = $this->admissions()->create([
                'date' => now(),
                'bed_id' => $bed?->id,
                'time' => now()->toTimeString(),
                'status' => 'Registered',
                'admitted_by' => auth()->user()->id
            ]);

            $bed?->update(['status' => 'occupied']);
        }
        return $admission;
    }

    /**
     * Get the antenatal care record for this visit (if applicable)
     */
    public function antenatalCare()
    {
        return $this->hasOne(AntenatalCare::class, 'patient_visit_id');
    }

    public function labours()
    {
        return $this->hasMany(Labour::class, 'patient_visit_id');
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class, 'patient_visit_id');
    }

    public function newborns()
    {
        return $this->hasMany(Newborn::class, 'patient_visit_id');
    }

    public function postnatalExaminations()
    {
        return $this->hasMany(PostnatalExamination::class, 'patient_visit_id');
    }

    public function childFollowUps()
    {
        return $this->hasMany(ChildFollowUp::class, 'patient_visit_id');
    }

    public function registeredAdmission() {
        return $this->admissions->where('status','registered')->first();
    }

    public function dischargedAdmission() {
        return $this->admissions->where('status','discharged')->first();
    }

    public function confirmAdmission() {
        $admission = null;
        foreach($this->admissions->where('status','confirmed') as $adm){
            $admission = $adm;
        }
        return $admission;
    }

    public function admissionStatus() {
        $status = 'Not Admitted';

        if($this->registeredAdmission()){
            $status = 'Registered';
        }elseif($this->confirmAdmission()){
            $status = 'Confirmed';
        }elseif($this->dischargedAdmission()){
            $status = 'Discharged';
        }

        return $status;
    }

    public function visitActivities() {
        return $this->hasMany(VisitActivity::class);
    }

    public function observations() {
        return $this->hasMany(Observation::class);
    }

    public function fluidBalances() {
        return $this->hasMany(FluidBalance::class);
    }

    public function generateFileOpeningBill($discount = 0) {
        $fileType = $this->patient->fileType;
        $service = Service::where('name',$fileType->name)->first();
        $baseAmount = $fileType ? $fileType->price : 3000;
        $discountAmount = $baseAmount * ($discount / 100);
        $finalAmount = max(0, $baseAmount - $discountAmount);
        
        $bill =$this->bills()->create([
            'department_id'=> auth()->user()->department->id,
            'service_description'=>'File Opening Charges',
            'amount'=>$baseAmount,
            'due_amount'=>$finalAmount,
            'discount'=>$discount,
            'bill_number'=>Bill::generateBillNumber(),
            'status'=>'pending',
            'issued_by'=>auth()->user()->id,
            'issued_date'=>date('d M, Y'),
            'due_date'=>now()->addDays(2)->toDateString()
        ]); 

        $bill->billServices()->create([
            'service_id'=>$service->id,
            'unit_price'=>$service->price,
            'quantity'=> 1,
            'subtotal' => $service->price
            ]);

    }

    public function generateServiceBillOf(Service $service, $discount = 0) {
        $baseAmount = $service->price;
        $discountAmount = $baseAmount * ($discount / 100);
        $finalAmount = max(0, $baseAmount - $discountAmount);
        
        $bill = $this->bills()->create([
            'service_description'=>'Charges for '.$service->name,
            'amount'=>$baseAmount,
            'due_amount'=>$finalAmount,
            'discount'=>$discount,
            'status'=>'pending',
            'issued_by'=>auth()->user()->id,
            'issued_date'=>date('d M, Y'),
            'due_date'=>now()->addDays(2)->toDateString(),
            'bill_number'=>Bill::generateBillNumber(),
            'department_id'=> auth()->user()->department->id,
        ]);
        // create service request
        $service->serviceRequests()->create([
            'patient_visit_id'=>$this->id,
            'requested_by'=>auth()->user()->id,
            'status'=>'pending',
            'payment_status'=>'pending',
            'bill_id'=>$bill->id,
            'request_date'=>date('d M, Y'),
            'requested_at'=> now(),
            'clinical_diagnoses'=> 'Request of '.$service->name
        ]);

        $bill->billServices()->create([
            'service_id'=>$service->id,
            'unit_price'=>$service->price,
            'quantity'=> 1,
            'subtotal' => $service->price
            ]);

        

    }

    public function generateBedSpaceBill(Admission $admission, Bed $bed, $days, $discount = 0) {
        $service = Service::where('category', 'Admission')
            ->where('name', 'like', '%Bed%')
            ->first();
        $baseAmount = $bed->ward->price * $days;
        $discountAmount = $baseAmount * ($discount / 100);
        $finalAmount = max(0, $baseAmount - $discountAmount);
        
        $bill = $this->bills()->create([
            'admission_id'=>$admission->id,
            'service_description'=>'Bed Space Charges',
            'amount'=>$baseAmount,
            'due_amount'=>$finalAmount,
            'discount'=>$discount,
            'status'=>'pending',
            'issued_by'=>auth()->user()->id,
            'issued_date'=>date('d M, Y'),
            'due_date'=>now()->addDays(2)->toDateString(),
            'bill_number'=>Bill::generateBillNumber(),
            'department_id'=> auth()->user()->department->id,
        ]);

        if ($service) {
            $bill->billServices()->create([
                'service_id' => $service->id,
                'unit_price' => $bed->ward->price,
                'quantity' => $days,
                'subtotal' => $baseAmount,
            ]);
        }

        
    }
}
