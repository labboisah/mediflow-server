<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\DepartmentServiceRequest;
use App\Models\Patient;

class VisitController extends Controller
{
    public function referredToDoctor($patientId) {
        $patient = Patient::find($patientId);
        
        $department = Department::where('name', 'Medical Services')->first();
        $service = $patient->currentVisit()->departmentServiceRequests()->latest()->first(); // Assuming 1 is the ID for the consultation service
       
        $departmentServiceRequest = DepartmentServiceRequest::create([
            'patient_visit_id' => $patient->currentVisit()->id,
            'department_id' => $department->id,
            'service_id' => $service->id,
            'requested_by' => auth()->id(),
            'status' => 'pending',
        ]);
        // LOG ACTIVITIES
        // You can add logging logic here if needed
        $patient->currentVisit()->visitActivities()->create([
            'activity' => 'Patient referred to doctor for further evaluation and management.',
            'recorded_by' => auth()->id(),
        ]);
        return redirect()->back()->with('success', 'Patient referred to doctor successfully');
        
    }

    public function referredToNurse($patientId) {
        $patient = Patient::find($patientId);
        
        $department = Department::where('name', 'Nursing Services')->first();
        $service = $patient->currentVisit()->departmentServiceRequests()->latest()->first(); // Assuming 1 is the ID for the consultation service
       
        $departmentServiceRequest = DepartmentServiceRequest::create([
            'patient_visit_id' => $patient->currentVisit()->id,
            'department_id' => $department->id,
            'service_id' => $service->id,
            'requested_by' => auth()->id(),
            'status' => 'pending',
        ]);

        // LOG ACTIVITIES

        $patient->currentVisit()->visitActivities()->create([
            'activity' => 'Patient referred back to nurse for further care and monitoring.',
            'recorded_by' => auth()->id(),
        ]);
        
        return redirect()->back()->with('success', 'Patient referred back to nurse successfully');
        
    }
}
