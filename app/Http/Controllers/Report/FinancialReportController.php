<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\BillService;
use App\Models\Expense;
use App\Models\Revenue;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\BillInvestigation;
use App\Models\Department;
use Barryvdh\DomPDF\Facade\Pdf;

class FinancialReportController extends Controller
{
    /**
     * Display the financial report filter form.
     */
    public function index()
    {
        return view('reports.finance.index');
    }
    /**
     * Show financial/billing report with filters.
     */
    public function search(Request $request)
    {
        $todayOnly = $request->boolean('today', false);

        if ($todayOnly) {
            $startDate = Carbon::today();
            $endDate = Carbon::today()->endOfDay();
        } else {
            $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()->format('Y-m-d')))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date', now()->format('Y-m-d')))->endOfDay();
        }

        if ($startDate->gt($endDate)) {
            return redirect()->back()->with('error', 'Start date cannot be after end date');
        }

        $sortBy = $request->input('sort_by', 'services');
        $data = null;
        $issuedBy = $this->effectiveIssuedBy($request);

        switch ($sortBy) {
            case 'users':
                $data = $this->getUsersFinancialData($startDate, $endDate, $issuedBy);
                break;
            case 'departments':
                $data = $this->getDepartmentsFinancialData($startDate, $endDate, $issuedBy);
                break;
            default:
                $data = $this->getServicesFinancialData($startDate, $endDate, $issuedBy);
                break;
        }

        return view('reports.finance.view', compact(
            'data',
            'sortBy',
            'startDate',
            'endDate',
            'todayOnly'
        ));
    }

    /**
     * Export financial report as CSV.
     */
    public function export(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);

        $payments = Payment::where('payments.status', 'completed')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->when($request->filled('search'), fn ($query) => $this->searchPayments($query, $request->input('search')))
            ->when($this->effectiveIssuedBy($request) !== '', fn ($query) => $query->where('paid_by', $this->effectiveIssuedBy($request)))
            ->with(['bill.patientVisit.patient', 'bill.walkinPatient', 'paymentMethod', 'recordedBy'])
            ->get();

        $filename = 'financial-report-' . date('Y-m-d') . '.csv';
        $handle = fopen('php://memory', 'w');

        // Add header
        fputcsv($handle, [
            'Payment ID',
            'Patient',
            'Hospital Number',
            'Amount',
            'Payment Method',
            'Insurance Provider',
            'Reference No.',
            'Paid By',
            'Payment Date'
        ]);

        // Add data
        foreach ($payments as $payment) {
            $patientDemographic = optional($payment->bill->patientVisit->patient)->demographic;
            $patientName = $patientDemographic && ($patientDemographic->first_name || $patientDemographic->last_name)
                ? trim($patientDemographic->first_name . ' ' . $patientDemographic->last_name)
                : optional($payment->bill->walkinPatient)->name ?? 'Unknown';

            $hospitalNumber = optional($payment->bill->patientVisit->patient)->hospital_number
                ?? '';

            fputcsv($handle, [
                $payment->payment_id,
                $patientName,
                $hospitalNumber,
                number_format($payment->amount, 2),
                optional($payment->paymentMethod)->name ?? $payment->payment_method_id,
                $payment->insurance_provider,
                $payment->reference_number,
                optional($payment->recordedBy)->name,
                $payment->payment_date->format('Y-m-d H:i:s')
            ]);
        }

        rewind($handle);

        return response(stream_get_contents($handle), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    public function pdf(Request $request)
    {
        [$startDate, $endDate] = $this->dateRange($request);

        $bills = Bill::query()
            ->with(['patientVisit.patient.demographic', 'walkinPatient', 'issuedBy'])
            ->whereBetween('issued_date', [$startDate, $endDate])
            ->when($request->filled('search'), fn ($query) => $this->searchBills($query, $request->input('search')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($this->effectiveIssuedBy($request) !== '', fn ($query) => $query->where('issued_by', $this->effectiveIssuedBy($request)))
            ->latest('issued_date')
            ->get();

        $payments = Payment::query()
            ->where('status', 'completed')
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->when($request->filled('search'), fn ($query) => $this->searchPayments($query, $request->input('search')))
            ->when($this->effectiveIssuedBy($request) !== '', fn ($query) => $query->where('paid_by', $this->effectiveIssuedBy($request)))
            ->get();

        $revenues = Revenue::query()
            ->with(['category', 'department', 'createdBy'])
            ->whereBetween('revenue_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($this->effectiveIssuedBy($request) !== '', fn ($query) => $query->where('created_by', $this->effectiveIssuedBy($request)))
            ->latest('revenue_date')
            ->get();

        $expenses = Expense::query()
            ->with(['category', 'department', 'createdBy'])
            ->whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($this->effectiveIssuedBy($request) !== '', fn ($query) => $query->where('created_by', $this->effectiveIssuedBy($request)))
            ->latest('expense_date')
            ->get();

        $summary = [
            'bill_count' => $bills->count(),
            'total_billed' => $bills->sum('amount'),
            'total_due' => $bills->sum('due_amount'),
            'total_collected' => $payments->sum('amount'),
            'total_revenue' => $revenues->sum('amount'),
            'total_expense' => $expenses->sum('amount'),
            'net_position' => $payments->sum('amount') + $revenues->sum('amount') - $expenses->sum('amount'),
        ];

        $hospital = $this->hospitalHeaderData();
        $generatedBy = $request->user();

        $pdf = Pdf::loadView('reports.pdf.finance-report', compact('bills', 'revenues', 'expenses', 'summary', 'startDate', 'endDate', 'hospital', 'generatedBy'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('billing-report-' . now()->format('Y-m-d') . '.pdf');
    }

    private function dateRange(Request $request): array
    {
        if ($request->boolean('today', false)) {
            return [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
        }

        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()->format('Y-m-d')))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', now()->format('Y-m-d')))->endOfDay();

        if ($startDate->gt($endDate)) {
            return [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$startDate, $endDate];
    }

    private function hospitalHeaderData(): array
    {
        return [
            'name' => strtoupper(config('app.title', config('app.name', 'FAYHOS'))),
            'address' => strtoupper(config('app.address', '')),
            'logo' => public_path('images/logo.png'),
        ];
    }

    private function effectiveIssuedBy(Request $request): string
    {
        if ($request->user()->hasRole('accountant') && ! $request->user()->hasRole('administrator')) {
            return (string) $request->user()->id;
        }

        return $request->input('issued_by', '');
    }

    private function searchBills($query, string $search)
    {
        return $query->where(function ($query) use ($search) {
            $query->where('bill_number', 'like', "%{$search}%")
                ->orWhere('service_description', 'like', "%{$search}%")
                ->orWhereHas('walkinPatient', fn ($patient) => $patient->where('name', 'like', "%{$search}%"))
                ->orWhereHas('patientVisit.patient', fn ($patient) => $patient->where('hospital_number', 'like', "%{$search}%"))
                ->orWhereHas('patientVisit.patient.demographic', function ($patient) use ($search) {
                    $patient->whereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"])
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
        });
    }

    private function searchPayments($query, string $search)
    {
        return $query->where(function ($query) use ($search) {
            $query->where('payment_id', 'like', "%{$search}%")
                ->orWhere('reference_number', 'like', "%{$search}%")
                ->orWhereHas('bill', fn ($bill) => $bill->where('bill_number', 'like', "%{$search}%"))
                ->orWhereHas('bill.walkinPatient', fn ($patient) => $patient->where('name', 'like', "%{$search}%"))
                ->orWhereHas('bill.patientVisit.patient', fn ($patient) => $patient->where('hospital_number', 'like', "%{$search}%"))
                ->orWhereHas('bill.patientVisit.patient.demographic', function ($patient) use ($search) {
                    $patient->whereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"])
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
        });
    }



    public function getUsersFinancialData($startDate, $endDate, string $issuedBy = '')
    {
        $summary = Bill::selectRaw('issued_by, SUM(amount) as total_amount, SUM(discount) as total_discount, SUM(due_amount) as total_due')
            ->whereBetween('issued_date', [$startDate, $endDate])
            ->when($issuedBy !== '', fn ($query) => $query->where('issued_by', $issuedBy))
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
            ->when($issuedBy !== '', fn ($query) => $query->where('issued_by', $issuedBy))
            ->orderByDesc('issued_date')
            ->get()
            ->groupBy('issued_by');

        return compact('summary', 'details');
    }

    public function getDepartmentsFinancialData($startDate, $endDate, string $issuedBy = '')
    {
        $data = [];

        foreach(Department::all() as $department) {
            // Process each department
            
                
                $serviceTotal = 0;
                $serviceCount = 0;
                $investigationTotal = 0;
                $investigationCount = 0;
                $billServices = null;
                $billInvestigations = null;

            foreach($department->services as $service) {
                
                // Process each service in the department
                $serviceTotal = BillService::join('bills', 'bill_services.bill_id', '=', 'bills.id')
                    ->where('bill_services.service_id', $service->id)
                    ->whereBetween('bills.issued_date', [$startDate, $endDate])
                    ->when($issuedBy !== '', fn ($query) => $query->where('bills.issued_by', $issuedBy))
                    ->sum('bill_services.subtotal');
                
                $serviceCount = BillService::join('bills', 'bill_services.bill_id', '=', 'bills.id')
                    ->where('bill_services.service_id', $service->id)
                    ->whereBetween('bills.issued_date', [$startDate, $endDate])
                    ->when($issuedBy !== '', fn ($query) => $query->where('bills.issued_by', $issuedBy))
                    ->count();
                
                $billServices = BillService::join('bills', 'bill_services.bill_id', '=', 'bills.id')
                    ->where('bill_services.service_id', $service->id)
                    ->whereBetween('bills.issued_date', [$startDate, $endDate])
                    ->when($issuedBy !== '', fn ($query) => $query->where('bills.issued_by', $issuedBy))
                    ->get();                
                
            }

            foreach($department->investigationTypes as $investigationType) {
                foreach($investigationType->investigations as $investigation) {
                    // Process each investigation in the department
                    $investigationTotal = BillInvestigation::join('bills', 'bill_investigations.bill_id', '=', 'bills.id')
                        ->where('bill_investigations.investigation_id', $investigation->id)
                        ->whereBetween('bills.issued_date', [$startDate, $endDate])
                        ->when($issuedBy !== '', fn ($query) => $query->where('bills.issued_by', $issuedBy))
                        ->sum('bill_investigations.subtotal');
                    
                    $investigationCount = BillInvestigation::join('bills', 'bill_investigations.bill_id', '=', 'bills.id')
                        ->where('bill_investigations.investigation_id', $investigation->id)
                        ->whereBetween('bills.issued_date', [$startDate, $endDate])
                        ->when($issuedBy !== '', fn ($query) => $query->where('bills.issued_by', $issuedBy))
                        ->count();
                    
                    $billInvestigations = BillInvestigation::join('bills', 'bill_investigations.bill_id', '=', 'bills.id')
                        ->where('bill_investigations.investigation_id', $investigation->id)
                        ->whereBetween('bills.issued_date', [$startDate, $endDate])
                        ->when($issuedBy !== '', fn ($query) => $query->where('bills.issued_by', $issuedBy))
                        ->get();  
                }            
            }
            
            $data[] = [
                'department_id' => $department->id,
                'department_name' => $department->name,
                'service_total' => $serviceTotal ?? 0,
                'service_count' => $serviceCount ?? 0,
                'services' => $billServices ?? collect(),
                'investigation_total' => $investigationTotal ?? 0,
                'investigation_discount' => $investigationDiscount ?? 0,
                'investigation_due' => $investigationDueAmount ?? 0,
                'investigation_count' => $investigationCount ?? 0,
                'investigations' => $billInvestigations ?? collect(),

            ]; 
            
        }
    
        $summary = collect($data)->map(function($item) {
            
            return (object) [
                'department_id' => $item['department_id'],
                'department_name' => $item['department_name'],
                'total_amount' => $item['service_total'] + $item['investigation_total'],
                'service_count'=>$item['service_count'],
                'investigation_count'=>$item['investigation_count'],
                'services' => $item['services'],
                'investigations' => $item['investigations'],
            ];
        });

        return compact('summary');
    }

    public function getServicesFinancialData($startDate, $endDate, string $issuedBy = '')
    {
        $summary = BillService::join('services', 'bill_services.service_id', '=', 'services.id')
            ->join('bills', 'bill_services.bill_id', '=', 'bills.id')
            ->whereBetween('bills.issued_date', [$startDate, $endDate])
            ->when($issuedBy !== '', fn ($query) => $query->where('bills.issued_by', $issuedBy))
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
            ->when($issuedBy !== '', fn ($query) => $query->where('bills.issued_by', $issuedBy))
            ->selectRaw('services.id as service_id, services.name as service_name, bills.bill_number, COALESCE(CONCAT(patient_demographics.first_name, " ", patient_demographics.last_name), walkin_patients.name) as patient_name, bill_services.quantity, bill_services.subtotal as amount, bills.issued_date')
            ->orderBy('services.name')
            ->orderByDesc('bills.issued_date')
            ->get()
            ->groupBy('service_id');

        return compact('summary', 'details');
    }
}
