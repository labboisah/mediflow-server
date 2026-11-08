<?php

namespace App\Http\Controllers\Nurse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;
use App\Models\PatientReferral;
use App\Models\Appointment;
use App\Models\NextOfKin;
use App\Models\PatientVisit;
use App\Models\ServiceRequest;
use App\Models\Admission;
use Auth;

class PatientController extends Controller
{
    public function index() {
        $requests = auth()->user()->pendingServiceRequests();

        return view('nurse.patient.index', compact('requests'));
    }

    public function show($patient) {
        return view('nurse.patient.show', compact('patient'));
    }

    public function admissions(Request $request) {
        $search = trim((string) $request->input('q'));

        $admissions = Admission::query()
            ->with(['patientVisit.patient.demographic', 'bed.ward', 'admittedBy'])
            ->whereDoesntHave('discharge')
            ->whereNotIn('status', ['discharged', 'Discharged', 'closed', 'Closed', 'absconded', 'Absconded', 'sama', 'SAMA'])
            ->when($search !== '', function ($query) use ($search) {
                $like = "%{$search}%";

                $query->where(function ($query) use ($like) {
                    $query
                        ->where('status', 'like', $like)
                        ->orWhereHas('patientVisit.patient', function ($patientQuery) use ($like) {
                            $patientQuery
                                ->where('hospital_number', 'like', $like)
                                ->orWhereHas('demographic', function ($demographicQuery) use ($like) {
                                    $demographicQuery
                                        ->where('first_name', 'like', $like)
                                        ->orWhere('last_name', 'like', $like)
                                        ->orWhere('middle_name', 'like', $like)
                                        ->orWhere('phone_number', 'like', $like);
                                });
                        });
                });
            })
            ->latest('date')
            ->get();

        return view('nurse.admissions.index', compact('admissions', 'search'));
    }

    public function recordAbsconded(Admission $admission) {
        $admission->status = 'absconded';
        $admission->save();

        // log activity
        $admission->patientVisit->visitActivities()->create([
            'recorded_by' => auth()->user()->id,
            'activity' => 'Patient marked as absconded by ' . auth()->user()->name,
        ]);

        return redirect()->back()->with('success', 'Patient marked as absconded.');
    }

    public function recordSAMA(Admission $admission) {
        $admission->status = 'sama';
        $admission->save();

        // log activity
        $admission->patientVisit->visitActivities()->create([
            'recorded_by' => auth()->user()->id,
            'activity' => 'Patient marked as Sign Against Medical Advice by ' . auth()->user()->name,
        ]);

        return redirect()->back()->with('success', 'Patient marked as Sign Against Medical Advice.');
    }

    public function complete(ServiceRequest $serviceRequest) {
        $serviceRequest->status = 'completed';
        $serviceRequest->performed_by = auth()->user()->id;
        $serviceRequest->completed_at = now();
        $serviceRequest->save();

        // log activity
        $serviceRequest->patientVisit->visitActivities()->create([
            'recorded_by' => auth()->user()->id,
            'activity' => 'Service "' . $serviceRequest->service->name . '" marked as completed by ' . auth()->user()->name,
        ]);
        return redirect()->back()->with('success', 'Service request marked as completed and bill generated.');
    }

    public function closeVisit(PatientVisit $patientVisit) {
        $patientVisit->status = 'Closed';
        $patientVisit->updated_at = now();
        $patientVisit->save();

        // log activity
        $patientVisit->visitActivities()->create([
            'recorded_by' => auth()->user()->id,
            'activity' => 'Patient visit marked as closed by ' . auth()->user()->name,
        ]);
        return redirect()->back()->with('success', 'Patient visit marked as closed.');
    }
}

