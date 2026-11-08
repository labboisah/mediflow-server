<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Bed;
use App\Models\Bill;
use App\Models\Department;
use App\Models\Investigation;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Service;
use App\Models\SyncOperation;
use App\Models\TemporaryPermission;
use App\Models\User;
use App\Models\WalkinPatient;
use App\Models\Ward;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.modern')]
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

        $canViewTechnicalRecords = auth()->user()?->hasRole('administrator') ?? false;
        $pageTitle = $canViewTechnicalRecords ? 'Superadmin Dashboard' : 'Medical Director Dashboard';
        $pageSubtitle = $canViewTechnicalRecords
            ? 'Live hospital, finance, access, and sync overview.'
            : 'Live hospital, patient flow, setup, and finance overview.';

        $hospitalData = $this->hospitalMetrics();
        $dashboardData = [
            'pageTitle' => $pageTitle,
            'pageSubtitle' => $pageSubtitle,
            'hospitalMetrics' => $hospitalData['metrics'],
            'setupMetrics' => $this->setupMetrics(),
            'visitStatusRows' => $hospitalData['visitStatusRows'],
            'lastUpdated' => now(),
            'canViewTechnicalRecords' => $canViewTechnicalRecords,
        ];

        if ($canViewTechnicalRecords) {
            $financeData = $this->financeMetrics();
            $dashboardData += [
                'accessMetrics' => $this->accessMetrics(),
                'financeMetrics' => $financeData['metrics'],
                'syncMetrics' => $this->syncMetrics(),
                'billStatusRows' => $financeData['billStatusRows'],
                'recentActivities' => $this->recentActivities(),
            ];
        }

        return view('components.admin.dashboard', $dashboardData);
    }

    private function accessMetrics(): array
    {
        $administrators = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'administrator'))
            ->selectRaw('COUNT(*)');
        $metrics = DB::query()
            ->selectSub(Role::query()->selectRaw('COUNT(*)'), 'roles')
            ->selectSub(Permission::query()->selectRaw('COUNT(*)'), 'permissions')
            ->selectSub(User::query()->selectRaw('COUNT(*)'), 'users')
            ->selectSub($administrators, 'administrators')
            ->selectSub(TemporaryPermission::active()->selectRaw('COUNT(*)'), 'temporary_permissions')
            ->first();

        return [
            'roles' => (int) $metrics->roles,
            'permissions' => (int) $metrics->permissions,
            'users' => (int) $metrics->users,
            'administrators' => (int) $metrics->administrators,
            'temporary_permissions' => (int) $metrics->temporary_permissions,
        ];
    }

    private function hospitalMetrics(): array
    {
        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();
        $patientMetrics = DB::query()
            ->selectSub(Patient::query()->selectRaw('COUNT(*)'), 'patients')
            ->selectSub(WalkinPatient::query()->selectRaw('COUNT(*)'), 'walkin_patients')
            ->first();
        $visitStatusRows = PatientVisit::query()
            ->selectRaw(
                'status as label,
                COUNT(id) as count,
                SUM(CASE WHEN visit_date >= ? AND visit_date < ? THEN 1 ELSE 0 END) as today_count',
                [$today, $tomorrow]
            )
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();

        return [
            'metrics' => [
                'patients' => (int) $patientMetrics->patients,
                'walkin_patients' => (int) $patientMetrics->walkin_patients,
                'visits' => (int) $visitStatusRows->sum('count'),
                'active_visits' => (int) ($visitStatusRows->firstWhere('label', 'Active')?->count ?? 0),
                'today_visits' => (int) $visitStatusRows->sum('today_count'),
            ],
            'visitStatusRows' => $visitStatusRows,
        ];
    }

    private function financeMetrics(): array
    {
        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();
        $billStatusRows = Bill::query()
            ->selectRaw(
                'status as label,
                COUNT(id) as count,
                SUM(amount) as amount,
                SUM(CASE WHEN issued_date >= ? AND issued_date < ? THEN 1 ELSE 0 END) as today_count,
                SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as open_count,
                COALESCE(SUM(CASE WHEN issued_date >= ? AND issued_date < ? THEN amount ELSE 0 END), 0) as today_amount',
                [$today, $tomorrow, 'pending', 'partial', $today, $tomorrow]
            )
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();
        $paymentMetrics = Payment::query()
            ->selectRaw(
                'COUNT(*) as payments,
                SUM(CASE WHEN payment_date >= ? AND payment_date < ? THEN 1 ELSE 0 END) as payments_today,
                COALESCE(SUM(CASE WHEN status = ? AND payment_date >= ? AND payment_date < ? THEN amount ELSE 0 END), 0) as collected_today',
                [$today, $tomorrow, 'completed', $today, $tomorrow]
            )
            ->first();

        return [
            'metrics' => [
                'bills' => (int) $billStatusRows->sum('count'),
                'today_bills' => (int) $billStatusRows->sum('today_count'),
                'open_bills' => (int) $billStatusRows->sum('open_count'),
                'total_billed_today' => (float) $billStatusRows->sum('today_amount'),
                'payments' => (int) $paymentMetrics->payments,
                'payments_today' => (int) $paymentMetrics->payments_today,
                'collected_today' => (float) $paymentMetrics->collected_today,
            ],
            'billStatusRows' => $billStatusRows,
        ];
    }

    private function setupMetrics(): array
    {
        $bedMetrics = Bed::query()->selectRaw(
            'COUNT(*) as beds, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as occupied_beds',
            ['occupied']
        );
        $metrics = DB::query()
            ->fromSub(Department::query()->selectRaw('COUNT(*) as departments'), 'department_metrics')
            ->crossJoinSub(Service::query()->selectRaw('COUNT(*) as services'), 'service_metrics')
            ->crossJoinSub(Investigation::query()->selectRaw('COUNT(*) as investigations'), 'investigation_metrics')
            ->crossJoinSub(Ward::query()->selectRaw('COUNT(*) as wards'), 'ward_metrics')
            ->crossJoinSub($bedMetrics, 'bed_metrics')
            ->first();

        return [
            'departments' => (int) $metrics->departments,
            'services' => (int) $metrics->services,
            'investigations' => (int) $metrics->investigations,
            'wards' => (int) $metrics->wards,
            'beds' => (int) $metrics->beds,
            'occupied_beds' => (int) $metrics->occupied_beds,
        ];
    }

    private function syncMetrics(): array
    {
        if (! Schema::hasTable('sync_operations')) {
            return [
                'pending' => 0,
                'failed' => 0,
                'synced_today' => 0,
                'latest' => null,
            ];
        }

        $today = Carbon::today();
        $syncedToday = SyncOperation::query()
            ->where('status', 'synced')
            ->whereDate('synced_at', $today)
            ->selectRaw('COUNT(*)');
        $counts = SyncOperation::query()
            ->selectRaw(
                'COUNT(CASE WHEN status = ? THEN 1 END) as pending,
                COUNT(CASE WHEN status = ? THEN 1 END) as failed,
                COUNT(*) as operations,
                MAX(updated_at) as latest_updated_at',
                ['pending', 'failed']
            )
            ->selectSub($syncedToday, 'synced_today')
            ->first();

        return [
            'pending' => (int) $counts->pending,
            'failed' => (int) $counts->failed,
            'synced_today' => (int) $counts->synced_today,
            'latest' => (int) $counts->operations > 0
                ? (object) [
                    'updated_at' => $counts->latest_updated_at
                        ? Carbon::parse($counts->latest_updated_at)
                        : null,
                ]
                : null,
        ];
    }

    private function recentActivities()
    {
        return AuditLog::query()
            ->leftJoin('users', function (JoinClause $join): void {
                $join->on('audit_logs.actor_id', '=', 'users.id')
                    ->whereNull('users.deleted_at');
            })
            ->select('audit_logs.*', 'users.name as actor_name')
            ->latest('audit_logs.created_at')
            ->limit(12)
            ->get()
            ->each(function (AuditLog $activity): void {
                $activity->setRelation(
                    'actor',
                    $activity->actor_name ? new User(['name' => $activity->actor_name]) : null
                );
            });
    }
}
