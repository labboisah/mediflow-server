<?php

namespace App\Models\Traits;

trait Reportable 
{
     /**
     * Generate report data for the user based on their permissions and activities.
     */
    public function generateReportData($date, $fromDate = null, $toDate = null)
    {
        $reportData = [];
        $startDate = $fromDate ? \Carbon\Carbon::parse($fromDate)->startOfDay() : \Carbon\Carbon::parse($date)->startOfDay();
        $endDate = $toDate ? \Carbon\Carbon::parse($toDate)->endOfDay() : \Carbon\Carbon::parse($date)->endOfDay();

        if($this->hasRole('admin')) {
            $reportData = $this->generateAdminReport($startDate, $endDate);
        }elseif($this->hasRole('doctor') && app(\App\Services\LicenseService::class)->moduleEnabled('doctor')) {
            $reportData = $this->generateDoctorReport($startDate, $endDate);
        }elseif($this->hasRole('nurse') && app(\App\Services\LicenseService::class)->moduleEnabled('nursing')) {
            $reportData = $this->generateNurseReport($startDate, $endDate);
        }elseif($this->hasRole('record') && app(\App\Services\LicenseService::class)->moduleEnabled('patient_records')) {
            $reportData = $this->generateRecordOfficerReport($startDate, $endDate);
        }elseif($this->hasRole('pharmacy') && app(\App\Services\LicenseService::class)->moduleEnabled('pharmacy')) {
            $reportData = $this->generatePharmacyReport($startDate, $endDate);
        }elseif($this->hasRole('lab') && app(\App\Services\LicenseService::class)->moduleEnabled('laboratory')) {
            $reportData = $this->generateLabReport($startDate, $endDate);
        }elseif($this->hasRole('accountant') && app(\App\Services\LicenseService::class)->moduleEnabled('billing')) {
            $reportData = $this->generateAccountantReport($startDate, $endDate);
        }elseif($this->hasRole('radiology') && app(\App\Services\LicenseService::class)->moduleEnabled('radiology')) {
            $reportData = $this->generateRadiologyReport($startDate, $endDate);
        }elseif($this->hasRole('midwife') && app(\App\Services\LicenseService::class)->moduleEnabled('maternity')) {
            $reportData = $this->generateMidwifeReport($startDate, $endDate);
        }elseif($this->hasRole('pharmacist') && app(\App\Services\LicenseService::class)->moduleEnabled('pharmacy')) {
            $reportData = $this->generatePharmacistReport($startDate, $endDate);
        }
        
        return $reportData;
    }

    /**
     * Generate admin system overview report
     */
    private function generateAdminReport($startDate, $endDate)
    {
        $reports = new \App\Models\AuditLog();
        $activities = $reports->whereBetween('created_at', [$startDate, $endDate])->get();
        
        return [
            'total_activities' => $activities->count(),
            'active_users' => User::whereHas('auditLogs', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })->distinct('id')->count(),
            'system_transactions' => $activities->count(),
            'activity_summary' => [],
            'system_health' => [
                'database' => 'Operational',
                'api' => 'Operational',
                'server_load' => 'Normal',
                'cache' => 'Active',
            ],
        ];
    }

    /**
     * Generate doctor activity report
     */
    private function generateDoctorReport($startDate, $endDate)
    {
        $patientVisits = \App\Models\PatientVisit::where('created_by', $this->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('patient.demographic', 'investigationRequests', 'serviceRequests')
            ->get();

        $consultations = $patientVisits->count();
        $investigationRequests = \App\Models\InvestigationRequest::where('requested_by', $this->id)
            ->whereBetween('requested_at', [$startDate, $endDate])
            ->with('patient.demographic', 'investigation')
            ->get();
        $serviceRequests = count($patientVisits) > 0 ? 
            $patientVisits->sum(fn($v) => $v->serviceRequests->count()) : 0;

        return [
            'consultations' => $consultations,
            'investigation_requests' => $investigationRequests->count(),
            'service_requests' => $serviceRequests,
            'consultations_details' => $patientVisits->map(function ($visit) {
                return [
                    'patient_name' => $visit->patient->demographic->first_name . ' ' . $visit->patient->demographic->last_name,
                    'diagnosis' => $visit->diagnosis ?? 'N/A',
                    'treatment' => $visit->treatment ?? 'N/A',
                    'time' => $visit->created_at->format('H:i'),
                ];
            })->toArray(),
            'investigation_requests_details' => $investigationRequests->map(function ($req) {
                return [
                    'patient_name' => $req->patient->demographic->first_name . ' ' . $req->patient->demographic->last_name,
                    'type' => $req->investigation->name ?? 'N/A',
                    'status' => $req->status ?? 'Pending',
                    'time' => $req->created_at->format('H:i'),
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate nurse activity report
     */
    private function generateNurseReport($startDate, $endDate)
    {
        $vitals = \App\Models\VitalSign::where('recorded_by', $this->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('patientVisit.patient.demographic')
            ->get();

        $patientVisits = \App\Models\PatientVisit::where('created_by', $this->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('patient.demographic')
            ->get();

        return [
            'patients_attended' => $patientVisits->count(),
            'vital_signs_recorded' => $vitals->count(),
            'vital_signs_details' => $vitals->map(function ($vital) {
                return [
                    'patient_name' => $vital->patientVisit->patient->demographic->first_name . ' ' . 
                                    $vital->patientVisit->patient->demographic->last_name,
                    'blood_pressure' => $vital->blood_pressure ?? 'N/A',
                    'temperature' => $vital->temperature ?? 'N/A',
                    'pulse' => $vital->pulse ?? 'N/A',
                    'respiration' => $vital->respiration_rate ?? 'N/A',
                    'time' => $vital->created_at->format('H:i'),
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate midwife activity report
     */
    private function generateMidwifeReport($startDate, $endDate)
    {
        $antenatalCares = \App\Models\AntenatalCare::where('recorded_by', $this->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('patient.demographic')
            ->get();

        $deliveries = \App\Models\Delivery::where('delivered_by', $this->id)
            ->whereBetween('delivery_date_time', [$startDate, $endDate])
            ->with('patient.demographic', 'labour')
            ->get();

        $postnatalExams = \App\Models\PostnatalExamination::where('recorded_by', $this->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('patient.demographic')
            ->get();

        $newbornExams = \App\Models\NewbornExamination::where('recorded_by', $this->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('newborn.patient.demographic')
            ->get();

        return [
            'antenatal_records' => $antenatalCares->count(),
            'deliveries' => $deliveries->count(),
            'postnatal_exams' => $postnatalExams->count(),
            'newborn_exams' => $newbornExams->count(),
            'antenatal_details' => $antenatalCares->map(function ($care) {
                return [
                    'patient_name' => $care->patient->demographic->first_name . ' ' . $care->patient->demographic->last_name,
                    'gestational_weeks' => $care->gestational_weeks ?? 'N/A',
                    'status' => $care->status ?? 'normal',
                    'time' => $care->created_at->format('H:i'),
                ];
            })->toArray(),
            'deliveries_details' => $deliveries->map(function ($delivery) {
                return [
                    'patient_name' => $delivery->patient->demographic->first_name . ' ' . $delivery->patient->demographic->last_name,
                    'delivery_type' => $delivery->type ?? 'N/A',
                    'status' => $delivery->status ?? 'Completed',
                    'time' => $delivery->created_at->format('H:i'),
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate pharmacist activity report
     */
    private function generatePharmacistReport($startDate, $endDate)
    {
        $prescriptions = \App\Models\Prescription::where('prescribe_by', $this->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('patientVisit.patient.demographic', 'prescriptionItems.medicine')
            ->get();

        $dispensed = $prescriptions->filter(fn($p) => $p->status === 'dispensed')->count();

        return [
            'prescriptions' => $prescriptions->count(),
            'medicines_dispensed' => $dispensed,
            'prescriptions_details' => $prescriptions->map(function ($prescription) {
                $medicine = $prescription->prescriptionItems->first()?->medicine;
                return [
                    'patient_name' => $prescription->patientVisit->patient->demographic->first_name . ' ' . 
                                    $prescription->patientVisit->patient->demographic->last_name,
                    'medicine' => $medicine->name ?? 'N/A',
                    'dosage' => $medicine->dosage ?? 'N/A',
                    'quantity' => $prescription->prescriptionItems->first()?->quantity ?? 'N/A',
                    'status' => $prescription->status ?? 'Active',
                    'time' => $prescription->created_at->format('H:i'),
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate lab technician activity report
     */
    private function generateLabReport($startDate, $endDate)
    {
        $completed = \App\Models\InvestigationRequest::where('performed_by', $this->id)
            ->whereBetween('completed_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->with('patient.demographic', 'investigation', 'investigationResults')
            ->get();

        $pending = \App\Models\InvestigationRequest::where('performed_by', $this->id)
            ->whereBetween('requested_at', [$startDate, $endDate])
            ->where('status', '!=', 'completed')
            ->with('patient.demographic', 'investigation')
            ->get();

        return [
            'investigations_completed' => $completed->count(),
            'investigations_pending' => $pending->count(),
            'investigations_completed_details' => $completed->map(function ($inv) {
                return [
                    'patient_name' => $inv->patient->demographic->first_name . ' ' . $inv->patient->demographic->last_name,
                    'type' => $inv->investigation->name ?? 'N/A',
                    'result' => $inv->investigationResults->first()?->result ?? 'N/A',
                    'date_completed' => $inv->completed_at?->format('M d, Y') ?? $inv->updated_at->format('M d, Y'),
                ];
            })->toArray(),
            'investigations_pending_details' => $pending->map(function ($inv) {
                return [
                    'patient_name' => $inv->patient->demographic->first_name . ' ' . $inv->patient->demographic->last_name,
                    'type' => $inv->investigation->name ?? 'N/A',
                    'date_requested' => $inv->created_at->format('M d, Y'),
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate pharmacy activity report
     */
    private function generatePharmacyReport($startDate, $endDate)
    {
        $prescriptions = \App\Models\Prescription::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'dispensed')
            ->with('patientVisit.patient.demographic', 'prescriptionItems.medicine')
            ->get();

        $medicines = $prescriptions->sum(fn($p) => $p->prescriptionItems->count());

        return [
            'prescriptions_dispensed' => $prescriptions->count(),
            'medicines_issued' => $medicines,
            'dispensed_details' => $prescriptions->map(function ($prescription) {
                return $prescription->prescriptionItems->map(function ($item) use ($prescription) {
                    return [
                        'patient_name' => $prescription->patientVisit->patient->demographic->first_name . ' ' . 
                                        $prescription->patientVisit->patient->demographic->last_name,
                        'medicine' => $item->medicine->name ?? 'N/A',
                        'quantity' => $item->quantity ?? 'N/A',
                        'dosage' => $item->medicine->dosage ?? 'N/A',
                        'time' => $prescription->created_at->format('H:i'),
                    ];
                })->toArray();
            })->flatten(1)->toArray(),
        ];
    }

    /**
     * Generate radiology activity report
     */
    private function generateRadiologyReport($startDate, $endDate)
    {
        // Using Investigation model as placeholder since Radiograph model doesn't exist
        $radiographs = \App\Models\InvestigationRequest::where('conducted_by', $this->id)
            ->whereHas('investigation', fn($q) => $q->whereIn('category', ['radiology', 'imaging', 'x-ray']))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $reports = $radiographs->filter(fn($r) => !is_null($r->report))->count();

        return [
            'radiographs_completed' => $radiographs->count(),
            'radiology_reports' => $reports,
            'radiographs_details' => $radiographs->map(function ($radio) {
                return [
                    'patient_name' => $radio->patient->demographic->first_name . ' ' . $radio->patient->demographic->last_name,
                    'type' => $radio->investigation->name ?? 'X-Ray',
                    'body_part' => $radio->investigation->description ?? 'N/A',
                    'date_completed' => $radio->completed_at?->format('M d, Y') ?? $radio->updated_at->format('M d, Y'),
                ];
            })->toArray(),
            'radiology_reports_details' => $radiographs->filter(fn($r) => !is_null($r->report))->map(function ($radio) {
                return [
                    'patient_name' => $radio->patient->demographic->first_name . ' ' . $radio->patient->demographic->last_name,
                    'type' => $radio->investigation->name ?? 'X-Ray',
                    'findings' => $radio->report ?? 'No findings recorded',
                    'date_written' => $radio->updated_at->format('M d, Y'),
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate record officer activity report
     */
    private function generateRecordOfficerReport($startDate, $endDate)
    {
        $patientVisits = \App\Models\PatientVisit::where('created_by', $this->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $bills = \App\Models\Bill::where('issued_by', $this->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $serviceRequests = \App\Models\ServiceRequest::where('requested_by', $this->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $investigationRequests = \App\Models\InvestigationRequest::where('requested_by', $this->id)
            ->whereBetween('requested_at', [$startDate, $endDate])
            ->get();
        $registeredPatients = \App\Models\PatientDemographic::whereBetween('created_at', [$startDate, $endDate])->count();

        $walkinPatients = \App\Models\WalkinPatient::whereBetween('created_at', [$startDate, $endDate])->count();

        return [
            'registered_patients' => $registeredPatients,
            'walkin_patients' => $walkinPatients,
            'patient_visits' => $patientVisits->count(),
            'bills_created' => $bills->count(),
            'service_requests' => $serviceRequests->count(),
            'investigation_requests' => $investigationRequests->count(),
            'patient_visits_details' => $patientVisits->map(function ($visit) {
                return [
                    'patient_name' => $visit->patient->demographic->first_name . ' ' . $visit->patient->demographic->last_name,
                    'diagnosis' => $visit->diagnosis ?? 'N/A',
                    'treatment' => $visit->treatment ?? 'N/A',
                    'time' => $visit->created_at->format('H:i'),
                ];
            })->toArray(),
            'bills_details' => $bills->map(function ($bill) {
                if($bill->walkinPatient) {
                    $patientName = $bill->walkinPatient->name;
                }else {
                    $patientName = $bill->patientVisit->patient->demographic->first_name . ' ' . $bill->patientVisit->patient->demographic->last_name;
                }
                return [
                    'patient_name' => $patientName,
                    'amount' => $bill->total_amount ?? '0',
                    'status' => $bill->status ?? 'Pending',
                    'date_created' => $bill->created_at->format('M d, Y'),
                ];
            })->toArray(),
            'service_requests_details' => $serviceRequests->map(function ($service) {
                if($service->patientVisit) {
                    $patientName = $service->patientVisit->patient->demographic->first_name . ' ' . $service->patientVisit->patient->demographic->last_name;
                }else {
                    $patientName = $service->walkinPatient ? $service->walkinPatient->name : 'N/A';
                }
                return [
                    'patient_name' => $patientName,
                    'type' => $service->type ?? 'N/A',
                    'status' => $service->status ?? 'Pending',
                    'date_created' => $service->created_at->format('M d, Y'),
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate accountant activity report
     */
    private function generateAccountantReport($startDate, $endDate)
    {
        $payments = \App\Models\Payment::where('paid_by', $this->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $bills = \App\Models\Bill::whereBetween('created_at', [$startDate, $endDate])->get();

        $totalPayments = $payments->sum('amount');
        $totalBills = $bills->sum('total_amount');

        return [
            'payments_recorded' => $payments->count(),
            'bills_created' => $bills->count(),
            'total_payment_amount' => number_format($totalPayments, 2),
            'total_bill_amount' => number_format($totalBills, 2),
            'payments_details' => $payments->map(function ($payment) {
                return [
                    'patient_name' => $payment->bill->patientVisit->patient->demographic->first_name . ' ' . 
                                    $payment->bill->patientVisit->patient->demographic->last_name,
                    'amount' => number_format($payment->amount, 2),
                    'payment_method' => $payment->paymentMethod->name ?? 'N/A',
                    'status' => $payment->status ?? 'Completed',
                    'time' => $payment->created_at->format('H:i'),
                ];
            })->toArray(),
            'bills_details' => $bills->map(function ($bill) {
                if($bill->walkinPatient) {
                    $patientName = $bill->walkinPatient->name;
                }else {
                        $patientName = $bill->patientVisit->patient->demographic->first_name . ' ' . $bill->patientVisit->patient->demographic->last_name;
                    }   
                return [
                    'patient_name' => $patientName,
                    'amount' => number_format($bill->total_amount, 2),
                    'status' => $bill->status ?? 'Pending',
                    'date_created' => $bill->created_at->format('M d, Y'),
                ];
            })->toArray(),
        ];
    }

}