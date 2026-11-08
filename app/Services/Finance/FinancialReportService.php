<?php

namespace App\Finance\Services;

use App\Models\Bill;
use App\Models\BillService;

trait FinancialReportService
{
    public function getUsersFinancialData($startDate, $endDate)
    {
        $summary = Bill::selectRaw('issued_by, SUM(amount) as total_amount, SUM(discount) as total_discount, SUM(due_amount) as total_due')
            ->whereBetween('issued_date', [$startDate, $endDate])
            ->groupBy('issued_by')
            ->with('issuedBy:id,name')
            ->get()
            ->map(function ($item) {
                return (object) [
                    'issued_by' => $item->issued_by,
                    'user_name' => optional($item->issuedBy)->name ?? 'Unknown',
                    'total_amount' => $item->total_amount,
                    'total_discount' => $item->total_discount,
                    'total_due' => $item->total_due,
                ];
            });

        $details = Bill::with(['patientVisit.patient.demographic', 'walkinPatient'])
            ->whereBetween('issued_date', [$startDate, $endDate])
            ->orderByDesc('issued_date')
            ->get()
            ->groupBy('issued_by');

        return compact('summary', 'details');
    }

    public function getDepartmentsFinancialData($startDate, $endDate)
    {
        $rows = BillService::join('services', 'bill_services.service_id', '=', 'services.id')
            ->join('departments', 'services.department_id', '=', 'departments.id')
            ->join('bills', 'bill_services.bill_id', '=', 'bills.id')
            ->whereBetween('bills.issued_date', [$startDate, $endDate])
            ->selectRaw('departments.id as department_id, departments.name as department_name, services.id as service_id, services.name as service_name, SUM(bill_services.subtotal) as service_total, SUM(bill_services.quantity) as total_quantity, COUNT(bill_services.id) as service_count')
            ->groupBy('departments.id', 'departments.name', 'services.id', 'services.name')
            ->orderBy('departments.name')
            ->orderByDesc('service_total')
            ->get();

        $summary = $rows->groupBy('department_id')->map(function ($services, $departmentId) {
            return (object) [
                'department_id' => $departmentId,
                'department_name' => $services->first()->department_name,
                'total_amount' => $services->sum('service_total'),
                'service_count' => $services->sum('service_count'),
                'services' => $services,
            ];
        })->values();

        return compact('summary');
    }

    public function getServicesFinancialData($startDate, $endDate)
    {
        $summary = BillService::join('services', 'bill_services.service_id', '=', 'services.id')
            ->join('bills', 'bill_services.bill_id', '=', 'bills.id')
            ->whereBetween('bills.issued_date', [$startDate, $endDate])
            ->selectRaw('services.id as service_id, services.name as service_name, SUM(bill_services.subtotal) as total_amount, SUM(bill_services.quantity) as total_quantity, COUNT(bill_services.id) as service_count')
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('total_amount')
            ->get();

        $details = BillService::join('services', 'bill_services.service_id', '=', 'services.id')
            ->join('bills', 'bill_services.bill_id', '=', 'bills.id')
            ->leftJoin('patient_visits', 'bills.patient_visit_id', '=', 'patient_visits.id')
            ->leftJoin('patients', 'patient_visits.patient_id', '=', 'patients.id')
            ->leftJoin('patient_demographics', 'patients.id', '=', 'patient_demographics.patient_id')
            ->leftJoin('walkin_patients', 'bills.walkin_id', '=', 'walkin_patients.id')
            ->whereBetween('bills.issued_date', [$startDate, $endDate])
            ->selectRaw('services.id as service_id, services.name as service_name, bills.bill_number, COALESCE(CONCAT(patient_demographics.first_name, " ", patient_demographics.last_name), walkin_patients.name) as patient_name, bill_services.quantity, bill_services.subtotal as amount, bills.issued_date')
            ->orderBy('services.name')
            ->orderByDesc('bills.issued_date')
            ->get()
            ->groupBy('service_id');

        return compact('summary', 'details');
    }
}
