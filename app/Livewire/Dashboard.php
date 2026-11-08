<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Bill;
use App\Models\InvestigationRequest;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\Payment;
use App\Models\PharmacyDispense;
use App\Models\Prescription;
use App\Models\ServiceRequest;
use App\Models\StockTransaction;
use App\Models\VisitActivity;
use App\Models\WalkinPatient;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $license = app(\App\Services\LicenseService::class);
        if (config('central_licensing.enabled') || $license->currentLicense()) {
            return view('components.installation-dashboard', [
                'packageLabel' => config('mediflow_modules.plans.'.$license->currentPlan().'.label', 'Installation'),
                'groups' => app(\App\Services\SidebarService::class)->groupsFor(auth()->user()),
            ])->layout('layouts.modern');
        }

        $user = auth()->user();

        return view('components.dashboard', [
            'user' => $user,
            'cards' => $this->cards($user),
            'quickActions' => $this->quickActions($user),
            'recentActivities' => $this->recentActivities($user),
            'pharmacyDashboard' => ($user->hasRole('pharmacist') || $this->canManagePharmacy($user)) ? $this->pharmacyDashboard() : null,
            'lastUpdated' => now(),
        ])->layout('layouts.modern');
    }

    private function cards($user): Collection
    {
        $cards = collect();
        $canManagePharmacy = $this->canManagePharmacy($user);

        if ($user->hasRole('record')) {
            $cards = $cards->merge([
                $this->card('Total Patients', Patient::count(), 'bi-people-fill', 'text-primary', 'All registered patient records'),
                $this->card('Registered Today', Patient::whereDate('created_at', today())->count(), 'bi-person-plus-fill', 'text-success', 'New records today'),
                $this->card('Today Visits', PatientVisit::whereDate('visit_date', today())->count(), 'bi-stethoscope', 'text-warning', 'Visits opened today'),
                $this->card('Walk-in Patients', WalkinPatient::count(), 'bi-person-check', 'text-info', 'Walk-in records'),
            ]);
        }

        if ($user->hasRole('accountant')) {
            $cards = $cards->merge([
                $this->card('My Bills', Bill::where('issued_by', $user->id)->count(), 'bi-receipt', 'text-primary', 'Bills issued by you'),
                $this->card('My Open Bills', Bill::where('issued_by', $user->id)->whereIn('status', ['pending', 'partial'])->count(), 'bi-exclamation-circle', 'text-warning', 'Pending or partial bills'),
                $this->card('Collected Today', number_format($this->paymentsByUser($user->id)->whereBetween('payment_date', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])->sum('amount'), 2), 'bi-cash-coin', 'text-success', 'Your completed payments today'),
                $this->card('My Payments', Payment::where('paid_by', $user->id)->count(), 'bi-credit-card-2-front', 'text-info', 'Payments recorded by you'),
            ]);
        }

        if ($user->hasRole('finance_officer')) {
            $cards = $cards->merge([
                $this->card('Bills', Bill::count(), 'bi-receipt', 'text-primary', 'All bill records'),
                $this->card('Open Bills', Bill::whereIn('status', ['pending', 'partial'])->count(), 'bi-exclamation-circle', 'text-warning', 'Pending or partial bills'),
                $this->card('Payments Today', number_format(Payment::where('status', 'completed')->whereBetween('payment_date', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])->sum('amount'), 2), 'bi-cash-coin', 'text-success', 'Completed payments today'),
                $this->card('Total Payments', Payment::count(), 'bi-credit-card-2-front', 'text-info', 'All payment records'),
            ]);
        }

        if ($user->hasAnyRole(['doctor', 'nurse'])) {
            $cards = $cards->merge([
                $this->card('Department Requests', $this->departmentServiceRequests($user)->count(), 'bi-clipboard-pulse', 'text-primary', 'Requests in your department'),
                $this->card('Pending Requests', $this->departmentServiceRequests($user)->where('status', 'Pending')->count(), 'bi-hourglass-split', 'text-warning', 'Awaiting action'),
                $this->card('Completed Today', $this->departmentServiceRequests($user)->where('status', 'Completed')->whereDate('completed_at', today())->count(), 'bi-check-circle', 'text-success', 'Completed today'),
            ]);
        }

        if ($user->hasAnyRole(['lab_technician', 'lab_scientist', 'radiologist'])) {
            $cards = $cards->merge([
                $this->card('Investigation Requests', $this->departmentInvestigationRequests($user)->count(), 'bi-clipboard2-pulse', 'text-primary', 'Department investigation work'),
                $this->card('Pending Investigations', $this->departmentInvestigationRequests($user)->where('status', 'Pending')->count(), 'bi-hourglass-split', 'text-warning', 'Awaiting result'),
                $this->card('Completed Today', $this->departmentInvestigationRequests($user)->where('status', 'Completed')->whereDate('completed_at', today())->count(), 'bi-check-circle', 'text-success', 'Completed today'),
            ]);
        }

        if ($user->hasRole('midwife')) {
            $midwife = $user->getMidwifeData();

            $cards = $cards->merge([
                $this->card('Antenatal Today', $midwife['antenatal_today'] ?? 0, 'bi-heart-pulse-fill', 'text-primary', 'ANC records today'),
                $this->card('Labour In Progress', $midwife['labour_in_progress'] ?? 0, 'bi-activity', 'text-warning', 'Active labour cases'),
                $this->card('Deliveries Today', $midwife['delivery_today'] ?? 0, 'bi-hospital-fill', 'text-success', 'Deliveries recorded today'),
                $this->card('Newborn Today', $midwife['newborn_today'] ?? 0, 'bi-bandaid-fill', 'text-info', 'Newborn records today'),
            ]);
        }

        if ($user->hasRole('pharmacist') || $canManagePharmacy) {
            $cards = $cards->merge([
                $this->card('Submitted Prescriptions', Prescription::where('status', 'submitted')->count(), 'bi-file-medical', 'text-primary', 'Awaiting pharmacy action'),
                $this->card('Transactions Today', $this->safeCount('stock_transactions', 'created_at'), 'bi-arrow-left-right', 'text-success', 'Stock activity today'),
                $this->card('Dispenses Today', $this->safeCount('pharmacy_dispenses', 'created_at'), 'bi-capsule-pill', 'text-warning', 'Medicines dispensed today'),
            ]);

            if ($canManagePharmacy) {
                $cards->push($this->card('Low Stock Batches', MedicineBatch::where('quantity_remaining', '<=', 10)->count(), 'bi-exclamation-triangle', 'text-danger', 'Batches at or below 10 units'));
            }
        }

        if ($user->hasRole('administrator')) {
            $cards = $cards->merge([
                $this->card('Patients', Patient::count(), 'bi-people-fill', 'text-primary', 'All patient records'),
                $this->card('Active Visits', PatientVisit::where('status', 'Active')->count(), 'bi-clipboard-pulse', 'text-success', 'Currently active visits'),
                $this->card('Bills Today', Bill::whereBetween('issued_date', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])->count(), 'bi-receipt', 'text-warning', 'Bills issued today'),
                $this->card('Payments Today', Payment::whereBetween('payment_date', [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()])->count(), 'bi-cash-coin', 'text-info', 'Payments recorded today'),
            ]);
        }

        if ($user->hasRole('medical_director')) {
            $cards = $cards->merge([
                $this->card('Patients', Patient::count(), 'bi-people-fill', 'text-primary', 'All patient records'),
                $this->card('Active Visits', PatientVisit::where('status', 'Active')->count(), 'bi-clipboard-pulse', 'text-success', 'Currently active visits'),
            ]);
        }

        if ($cards->isEmpty()) {
            $cards->push($this->card('My Activities Today', $this->auditActivityCount($user->id), 'bi-activity', 'text-success', 'Actions recorded today'));
        }

        return $cards;
    }

    private function quickActions($user): Collection
    {
        $actions = collect([
            ['label' => 'My Activities', 'description' => 'Review your recorded work', 'icon' => 'bi-activity', 'route' => route('reports.my-activities.index'), 'class' => 'btn-outline-success'],
        ]);

        if ($user->hasRole('record')) {
            $actions = $actions->merge([
                ['label' => 'Register Patient', 'description' => 'Add new patient', 'icon' => 'bi-person-plus', 'route' => route('record.patients.register.form'), 'class' => 'btn-outline-primary'],
                ['label' => 'Patients', 'description' => 'Browse records', 'icon' => 'bi-people', 'route' => route('record.patients.index'), 'class' => 'btn-outline-info'],
            ]);
        }

        if ($user->hasRole('accountant')) {
            $actions = $actions->merge([
                ['label' => 'Bills', 'description' => 'Manage billing', 'icon' => 'bi-receipt', 'route' => route('accountant.bills.index'), 'class' => 'btn-outline-primary'],
                ['label' => 'Payments', 'description' => 'Review payments', 'icon' => 'bi-credit-card', 'route' => route('accountant.payments.index'), 'class' => 'btn-outline-success'],
                ['label' => 'My Billing Report', 'description' => 'Your bill report', 'icon' => 'bi-file-earmark-text', 'route' => route('reports.finance.index'), 'class' => 'btn-outline-secondary'],
            ]);
        }

        if ($user->hasRole('finance_officer')) {
            $actions = $actions->merge([
                ['label' => 'Bills', 'description' => 'View and report bills', 'icon' => 'bi-receipt', 'route' => route('finance.bills.index'), 'class' => 'btn-outline-primary'],
                ['label' => 'Payments', 'description' => 'View and report payments', 'icon' => 'bi-credit-card', 'route' => route('finance.payments.index'), 'class' => 'btn-outline-success'],
                ['label' => 'Expenses', 'description' => 'Manage expenses', 'icon' => 'bi-cash-stack', 'route' => route('finance.expenses.index'), 'class' => 'btn-outline-danger'],
                ['label' => 'Revenues', 'description' => 'Manage revenues', 'icon' => 'bi-graph-up-arrow', 'route' => route('finance.revenues.index'), 'class' => 'btn-outline-info'],
            ]);
        }

        if ($user->hasRole('administrator')) {
            $actions = $actions->merge([
                ['label' => 'Admin Panel', 'description' => 'Live admin dashboard', 'icon' => 'bi-speedometer2', 'route' => route('admin.index'), 'class' => 'btn-outline-success'],
                ['label' => 'Users', 'description' => 'Manage access', 'icon' => 'bi-people-fill', 'route' => route('admin.users.index'), 'class' => 'btn-outline-primary'],
            ]);
        }

        if ($user->hasRole('medical_director')) {
            $actions = $actions->merge([
                ['label' => 'Medical Director Dashboard', 'description' => 'Hospital oversight', 'icon' => 'bi-speedometer2', 'route' => route('medical-director.index'), 'class' => 'btn-outline-success'],
                ['label' => 'Patient Register', 'description' => 'Review patient records', 'icon' => 'bi-file-earmark-spreadsheet', 'route' => route('medical-director.patient-register.index'), 'class' => 'btn-outline-primary'],
                ['label' => 'Billing Report', 'description' => 'Review finance summary', 'icon' => 'bi-file-earmark-text', 'route' => route('reports.finance.index'), 'class' => 'btn-outline-secondary'],
            ]);
        }

        if ($user->hasRole('pharmacist')) {
            $actions = $actions->merge([
                ['label' => 'Dispense Medicine', 'description' => 'Create pharmacy transaction', 'icon' => 'bi-receipt', 'route' => route('pharmacy.transactions.create'), 'class' => 'btn-outline-primary'],
            ]);
        }

        if ($this->canManagePharmacy($user)) {
            $actions = $actions->merge([
                ['label' => 'Medicines', 'description' => 'Manage medicine catalog', 'icon' => 'bi-capsule', 'route' => route('pharmacy.medicines.index'), 'class' => 'btn-outline-success'],
                ['label' => 'Stock', 'description' => 'Review pharmacy batches', 'icon' => 'bi-box', 'route' => route('pharmacy.stocks.index'), 'class' => 'btn-outline-warning'],
                ['label' => 'Finance', 'description' => 'Review pharmacy finance', 'icon' => 'bi-cash-stack', 'route' => route('pharmacy.finance.report'), 'class' => 'btn-outline-primary'],
                ['label' => 'Expiry Alerts', 'description' => 'Check expiring batches', 'icon' => 'bi-exclamation-triangle', 'route' => route('pharmacy.expiries.index'), 'class' => 'btn-outline-danger'],
            ]);
        }

        return $actions;
    }

    private function pharmacyDashboard(): array
    {
        $canManagePharmacy = $this->canManagePharmacy(auth()->user());

        return [
            'canManageInventory' => $canManagePharmacy,
            'pendingPrescriptions' => Prescription::with([
                'patientVisit.patient.demographic',
                'prescribedBy.department',
                'prescriptionItems.medicine.batches',
            ])
                ->where('status', 'submitted')
                ->latest()
                ->limit(8)
                ->get(),
            'expiringBatches' => $canManagePharmacy ? MedicineBatch::with('medicine')
                ->whereDate('expiry_date', '>=', today())
                ->whereDate('expiry_date', '<=', today()->addYears(3))
                ->orderBy('expiry_date')
                ->limit(8)
                ->get() : collect(),
            'lowStockBatches' => $canManagePharmacy ? MedicineBatch::with('medicine')
                ->where('quantity_remaining', '<=', 10)
                ->orderBy('quantity_remaining')
                ->limit(8)
                ->get() : collect(),
        ];
    }

    private function recentActivities($user): Collection
    {
        $activities = collect();

        $activities = $activities->merge(
            AuditLog::where('actor_id', $user->id)
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($activity) => [
                    'title' => ucwords(str_replace(['.', '_'], [' ', ' '], $activity->action)),
                    'subtitle' => $activity->model_type ? class_basename($activity->model_type) . ($activity->model_id ? ' #' . $activity->model_id : '') : 'System activity',
                    'created_at' => $activity->created_at,
                    'icon' => 'bi-shield-check',
                ])
        );

        $activities = $activities->merge(
            VisitActivity::with('patientVisit.patient.demographic')
                ->where('recorded_by', $user->id)
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($activity) {
                    $patient = $activity->patientVisit?->patient;

                    return [
                        'title' => $activity->activity,
                        'subtitle' => $patient ? (($patient->demographic?->full_name ?? 'Patient') . ' - ' . $patient->hospital_number) : 'Visit activity',
                        'created_at' => $activity->created_at,
                        'icon' => 'bi-activity',
                    ];
                })
        );

        $activities = $activities->merge(
            Bill::with(['patientVisit.patient.demographic', 'walkinPatient'])
                ->where('issued_by', $user->id)
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($bill) => [
                    'title' => 'Bill generated: ' . $bill->bill_number,
                    'subtitle' => $bill->patientName() . ' - ' . number_format((float) $bill->due_amount, 2),
                    'created_at' => $bill->created_at,
                    'icon' => 'bi-receipt',
                ])
        );

        $activities = $activities->merge(
            Payment::with(['bill.patientVisit.patient.demographic', 'bill.walkinPatient'])
                ->where('paid_by', $user->id)
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($payment) => [
                    'title' => 'Payment recorded: ' . $payment->payment_id,
                    'subtitle' => ($payment->bill?->patientName() ?? 'Payment') . ' - ' . number_format((float) $payment->amount, 2),
                    'created_at' => $payment->created_at,
                    'icon' => 'bi-cash-coin',
                ])
        );

        $activities = $activities->merge(
            ServiceRequest::with(['service', 'patientVisit.patient.demographic'])
                ->where(fn ($query) => $query->where('requested_by', $user->id)->orWhere('performed_by', $user->id))
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($request) => [
                    'title' => 'Service request: ' . ($request->service?->name ?? 'Service'),
                    'subtitle' => ($request->patientVisit?->patient?->demographic?->full_name ?? 'Patient') . ' - ' . ucfirst($request->status ?? 'pending'),
                    'created_at' => $request->created_at,
                    'icon' => 'bi-clipboard-pulse',
                ])
        );

        $activities = $activities->merge(
            InvestigationRequest::with(['investigation', 'patientVisit.patient.demographic', 'walkinPatient'])
                ->where(fn ($query) => $query->where('requested_by', $user->id)->orWhere('performed_by', $user->id))
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($request) => [
                    'title' => 'Investigation: ' . ($request->investigation?->name ?? 'Investigation'),
                    'subtitle' => ($request->patientVisit?->patient?->demographic?->full_name ?? $request->walkinPatient?->name ?? 'Patient') . ' - ' . ucfirst($request->status ?? 'pending'),
                    'created_at' => $request->created_at,
                    'icon' => 'bi-clipboard2-pulse',
                ])
        );

        $activities = $activities->merge(
            StockTransaction::with('stockTransactionItems.medicineBatch.medicine')
                ->where('created_by', $user->id)
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($transaction) => [
                    'title' => 'Pharmacy transaction: ' . ucfirst($transaction->type),
                    'subtitle' => number_format((float) $transaction->total_amount, 2),
                    'created_at' => $transaction->created_at,
                    'icon' => 'bi-capsule',
                ])
        );

        $activities = $activities->merge(
            PharmacyDispense::with('medicineBatch.medicine')
                ->where('created_by', $user->id)
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn ($dispense) => [
                    'title' => 'Medicine dispensed: ' . ($dispense->medicineBatch?->medicine?->name ?? 'Medicine'),
                    'subtitle' => 'Quantity: ' . $dispense->quantity,
                    'created_at' => $dispense->created_at,
                    'icon' => 'bi-capsule-pill',
                ])
        );

        return $activities
            ->filter(fn ($activity) => $activity['created_at'])
            ->sortByDesc('created_at')
            ->take(10)
            ->values();
    }

    private function auditActivityCount(int $userId): int
    {
        return AuditLog::where('actor_id', $userId)
            ->whereDate('created_at', today())
            ->count()
            + VisitActivity::where('recorded_by', $userId)
            ->latest()
            ->whereDate('created_at', today())
            ->count();
    }

    private function card(string $label, mixed $value, string $icon, string $iconClass, string $description): array
    {
        return compact('label', 'value', 'icon', 'iconClass', 'description');
    }

    private function paymentsByUser(int $userId)
    {
        return Payment::where('paid_by', $userId)->where('status', 'completed');
    }

    private function departmentServiceRequests($user)
    {
        return ServiceRequest::query()
            ->whereHas('service', fn ($query) => $query->where('department_id', $user->department_id));
    }

    private function departmentInvestigationRequests($user)
    {
        return InvestigationRequest::query()
            ->whereHas('investigation.investigationType', fn ($query) => $query->where('department_id', $user->department_id));
    }

    private function safeCount(string $table, string $dateColumn): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $dateColumn)) {
            return 0;
        }

        return DB::table($table)->whereDate($dateColumn, today())->count();
    }

    private function canManagePharmacy($user): bool
    {
        if (! $user) {
            return false;
        }

        $departmentName = strtolower((string) $user->department?->name);

        return $user->hasRole('pharmacist')
            || ($user->hasRole('head_of_department') && str_contains($departmentName, 'pharmacy'));
    }
}

