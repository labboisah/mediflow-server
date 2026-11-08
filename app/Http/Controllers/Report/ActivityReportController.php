<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ActivityReportController extends Controller
{
    public function export(Request $request, Department $department)
    {
        $definitions = $this->activeDefinitions($request, $department);
        $activities = $this->activities($request, $department, $definitions);
        [$startDate, $endDate] = $this->dateRange($request);

        $filename = 'activity-report-' . str($department->name)->slug() . '-' . now()->format('Y-m-d') . '.csv';
        $handle = fopen('php://memory', 'w');

        fputcsv($handle, [
            'Department',
            'Activity',
            'Details',
            'User',
            'Date',
            'Report Start',
            'Report End',
        ]);

        foreach ($activities as $activity) {
            fputcsv($handle, [
                $department->name,
                $activity->activity_label,
                $activity->subject,
                $activity->user_name,
                Carbon::parse($activity->occurred_at)->format('Y-m-d H:i:s'),
                $startDate->format('Y-m-d H:i:s'),
                $endDate->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($handle);

        return response(stream_get_contents($handle), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    public function pdf(Request $request, Department $department)
    {
        $department->load(['services', 'investigationTypes.investigations']);

        $definitions = $this->activeDefinitions($request, $department);
        $activities = $this->activities($request, $department, $definitions);
        $typeBreakdown = $this->typeBreakdown($activities);
        $userBreakdown = $this->userBreakdown($activities, $department);
        [$startDate, $endDate] = $this->dateRange($request);

        $summary = [
            'total' => $activities->count(),
            'service_count' => $typeBreakdown->whereIn('key', ['service_requests', 'completed_services'])->sum('count'),
            'investigation_count' => $typeBreakdown->whereIn('key', ['investigation_requests', 'completed_investigations'])->sum('count'),
            'active_users' => $userBreakdown->where('count', '>', 0)->count(),
        ];

        $summary['clinical_count'] = max($summary['total'] - $summary['service_count'] - $summary['investigation_count'], 0);

        $hospital = $this->hospitalHeaderData();
        $generatedBy = $request->user();

        $pdf = Pdf::loadView('reports.pdf.activity-report', compact(
            'department',
            'activities',
            'typeBreakdown',
            'userBreakdown',
            'summary',
            'startDate',
            'endDate',
            'hospital',
            'generatedBy'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('activity-report-' . str($department->name)->slug() . '-' . now()->format('Y-m-d') . '.pdf');
    }

    private function activities(Request $request, Department $department, array $definitions): Collection
    {
        $items = collect();

        foreach ($definitions as $key => $definition) {
            $table = $definition['table'];
            $subjectSelect = ! empty($definition['subject_column'])
                ? "{$definition['subject_column']} as subject"
                : DB::raw("CONCAT('Record #', {$table}.id) as subject");

            $items = $items->merge(
                $this->baseQuery($request, $department, $definition)
                    ->select([
                        "{$table}.id",
                        "{$table}.{$definition['date_column']} as occurred_at",
                        "{$table}.{$definition['user_column']} as user_id",
                        'users.name as user_name',
                    ])
                    ->selectRaw('? as activity_key, ? as activity_label', [$key, $definition['label']])
                    ->addSelect($subjectSelect)
                    ->orderByDesc("{$table}.{$definition['date_column']}")
                    ->get()
            );
        }

        return $items->sortByDesc('occurred_at')->values();
    }

    private function activityDefinitions(): array
    {
        return [
            'service_requests' => [
                'label' => 'Service Requests',
                'table' => 'service_requests',
                'user_column' => 'requested_by',
                'date_column' => 'requested_at',
                'department_scope' => 'services',
                'subject_column' => 'services.name',
            ],
            'completed_services' => [
                'label' => 'Completed Services',
                'table' => 'service_requests',
                'user_column' => 'performed_by',
                'date_column' => 'completed_at',
                'department_scope' => 'services',
                'subject_column' => 'services.name',
            ],
            'investigation_requests' => [
                'label' => 'Investigation Requests',
                'table' => 'investigation_requests',
                'user_column' => 'requested_by',
                'date_column' => 'requested_at',
                'department_scope' => 'investigations',
                'subject_column' => 'investigations.name',
            ],
            'completed_investigations' => [
                'label' => 'Completed Investigations',
                'table' => 'investigation_requests',
                'user_column' => 'performed_by',
                'date_column' => 'completed_at',
                'department_scope' => 'investigations',
                'subject_column' => 'investigations.name',
            ],
            'patient_visits' => ['label' => 'Patient Visits', 'table' => 'patient_visits', 'user_column' => 'created_by', 'date_column' => 'created_at'],
            'vital_signs' => ['label' => 'Vital Signs', 'table' => 'vital_signs', 'user_column' => 'recorded_by', 'date_column' => 'created_at'],
            'observations' => ['label' => 'Observations', 'table' => 'observations', 'user_column' => 'recorded_by', 'date_column' => 'created_at'],
            'fluid_balances' => ['label' => 'Fluid Balance', 'table' => 'fluid_balances', 'user_column' => 'recorded_by', 'date_column' => 'created_at'],
            'continuations' => ['label' => 'Continuations', 'table' => 'continuations', 'user_column' => 'written_by', 'date_column' => 'created_at'],
            'prescriptions' => ['label' => 'Prescriptions', 'table' => 'prescriptions', 'user_column' => 'prescribe_by', 'date_column' => 'created_at'],
            'antenatal_cares' => ['label' => 'Antenatal Care', 'table' => 'antenatal_cares', 'user_column' => 'recorded_by', 'date_column' => 'created_at'],
            'labours' => ['label' => 'Labour Registrations', 'table' => 'labours', 'user_column' => 'recorded_by', 'date_column' => 'created_at'],
            'labour_progress' => ['label' => 'Labour Progress', 'table' => 'labour_progress', 'user_column' => 'recorded_by', 'date_column' => 'created_at'],
            'deliveries' => ['label' => 'Deliveries', 'table' => 'deliveries', 'user_column' => 'delivered_by', 'date_column' => 'created_at'],
            'newborns' => ['label' => 'Newborn Records', 'table' => 'newborns', 'user_column' => 'recorded_by', 'date_column' => 'created_at'],
            'newborn_examinations' => ['label' => 'Newborn Examinations', 'table' => 'newborn_examinations', 'user_column' => 'recorded_by', 'date_column' => 'created_at'],
            'postnatal_examinations' => ['label' => 'Postnatal Examinations', 'table' => 'postnatal_examinations', 'user_column' => 'recorded_by', 'date_column' => 'created_at'],
            'child_follow_ups' => ['label' => 'Child Follow-ups', 'table' => 'child_follow_ups', 'user_column' => 'recorded_by', 'date_column' => 'created_at'],
            'admissions' => ['label' => 'Admissions', 'table' => 'admissions', 'user_column' => 'admitted_by', 'date_column' => 'created_at'],
            'patient_admissions' => ['label' => 'Patient Admissions', 'table' => 'patient_admissions', 'user_column' => 'admitted_by', 'date_column' => 'created_at'],
            'visit_activities' => ['label' => 'Visit Activities', 'table' => 'visit_activities', 'user_column' => 'recorded_by', 'date_column' => 'created_at', 'subject_column' => 'visit_activities.activity'],
        ];
    }

    private function activeDefinitions(Request $request, Department $department): array
    {
        return collect($this->activityDefinitions())
            ->filter(fn ($definition, $key) => (! $request->filled('activity_type') || $request->input('activity_type') === $key)
                && $this->definitionIsAvailable($definition)
                && $this->definitionBelongsToDepartment($definition, $department))
            ->all();
    }

    private function definitionIsAvailable(array $definition): bool
    {
        return app(\App\Services\LicenseService::class)->reportTableEnabled($definition['table'])
            && Schema::hasTable($definition['table'])
            && Schema::hasColumn($definition['table'], $definition['user_column'])
            && Schema::hasColumn($definition['table'], $definition['date_column']);
    }

    private function definitionBelongsToDepartment(array $definition, Department $department): bool
    {
        return match ($definition['department_scope'] ?? 'users') {
            'services' => $department->services()->exists(),
            'investigations' => $department->investigationTypes()->exists(),
            default => User::where('department_id', $department->id)->exists(),
        };
    }

    private function baseQuery(Request $request, Department $department, array $definition)
    {
        [$startDate, $endDate] = $this->dateRange($request);
        $table = $definition['table'];

        $query = DB::table($table)
            ->leftJoin('users', "{$table}.{$definition['user_column']}", '=', 'users.id')
            ->whereBetween("{$table}.{$definition['date_column']}", [$startDate, $endDate])
            ->whereNotNull("{$table}.{$definition['user_column']}");

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull("{$table}.deleted_at");
        }

        if (($definition['department_scope'] ?? null) === 'services') {
            $query->join('services', "{$table}.service_id", '=', 'services.id')
                ->where('services.department_id', $department->id);
        } elseif (($definition['department_scope'] ?? null) === 'investigations') {
            $query->join('investigations', "{$table}.investigation_id", '=', 'investigations.id')
                ->join('investigation_types', 'investigations.investigation_type_id', '=', 'investigation_types.id')
                ->where('investigation_types.department_id', $department->id);
        } else {
            $query->whereIn("{$table}.{$definition['user_column']}", $this->departmentUserIds($department));
        }

        if ($request->filled('user_id')) {
            $query->where("{$table}.{$definition['user_column']}", $request->input('user_id'));
        }

        if ($request->filled('search')) {
            $this->applySearch($query, $definition, $request->input('search'));
        }

        return $query;
    }

    private function applySearch($query, array $definition, string $search): void
    {
        $table = $definition['table'];

        $query->where(function ($builder) use ($definition, $search, $table) {
            $builder->where('users.name', 'like', "%{$search}%")
                ->orWhere("{$table}.id", 'like', "%{$search}%");

            if (! empty($definition['subject_column'])) {
                $builder->orWhere($definition['subject_column'], 'like', "%{$search}%");
            }

            if (Schema::hasColumn($table, 'status')) {
                $builder->orWhere("{$table}.status", 'like', "%{$search}%");
            }
        });
    }

    private function typeBreakdown(Collection $activities): Collection
    {
        return $activities
            ->groupBy('activity_key')
            ->map(fn ($rows) => (object) [
                'key' => $rows->first()->activity_key,
                'label' => $rows->first()->activity_label,
                'count' => $rows->count(),
            ])
            ->sortByDesc('count')
            ->values();
    }

    private function userBreakdown(Collection $activities, Department $department): Collection
    {
        $activeRows = $activities
            ->groupBy('user_id')
            ->map(fn ($rows) => (object) [
                'user_id' => $rows->first()->user_id,
                'label' => $rows->first()->user_name ?? 'Unknown',
                'count' => $rows->count(),
            ]);

        $departmentUsers = User::where('department_id', $department->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $departmentUserIds = $departmentUsers->pluck('id');

        return $departmentUsers
            ->map(fn ($user) => (object) [
                'user_id' => $user->id,
                'label' => $user->name,
                'count' => (int) ($activeRows[$user->id]->count ?? 0),
            ])
            ->merge($activeRows->reject(fn ($row, $userId) => $departmentUserIds->contains($userId)))
            ->sortByDesc('count')
            ->values();
    }

    private function departmentUserIds(Department $department): array
    {
        return User::where('department_id', $department->id)->pluck('id')->all();
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
}
