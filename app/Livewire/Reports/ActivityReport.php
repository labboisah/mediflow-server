<?php

namespace App\Livewire\Reports;

use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class ActivityReport extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public Department $department;
    public string $search = '';
    public string $userId = '';
    public string $activityType = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public bool $todayOnly = false;
    public string $chartBreakdown = 'types';
    public string $chartType = 'bar';

    public function mount(Department $department): void
    {
        $this->department = $department->load(['services', 'investigationTypes.investigations']);
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingUserId(): void
    {
        $this->resetPage();
    }

    public function updatingActivityType(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedTodayOnly(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'userId', 'activityType', 'todayOnly']);
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function render()
    {
        $availableDefinitions = $this->availableDefinitions();
        $availableTypeBreakdown = $this->typeBreakdown($availableDefinitions);
        $definitions = $this->activeDefinitions($availableDefinitions);
        $users = $this->departmentUsers();
        $typeBreakdown = $this->typeBreakdown($definitions);
        $userBreakdown = $this->userBreakdown($definitions, $users);

        return view('components.reports.activity-report', [
            'department' => $this->department,
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'users' => $users,
            'activityTypes' => $availableTypeBreakdown
                ->map(fn ($row) => [
                    'key' => $row->key,
                    'label' => $row->label,
                ])
                ->values(),
            'summary' => $this->summary($typeBreakdown, $userBreakdown),
            'typeBreakdown' => $typeBreakdown,
            'userBreakdown' => $userBreakdown,
            'chartPayload' => $this->chartPayload($typeBreakdown, $userBreakdown),
            'recentActivities' => $this->recentActivities($definitions),
            'exportUrl' => route('reports.activities.export', ['department' => $this->department] + $this->exportParameters()),
            'pdfUrl' => route('reports.activities.pdf', ['department' => $this->department] + $this->exportParameters()),
        ]);
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

    private function availableDefinitions(): array
    {
        return collect($this->activityDefinitions())
            ->filter(fn ($definition) => $this->definitionIsAvailable($definition)
                && $this->definitionBelongsToDepartment($definition))
            ->all();
    }

    private function activeDefinitions(array $availableDefinitions): array
    {
        return collect($availableDefinitions)
            ->filter(fn ($definition, $key) => $this->activityType === '' || $this->activityType === $key)
            ->all();
    }

    private function definitionIsAvailable(array $definition): bool
    {
        return app(\App\Services\LicenseService::class)->reportTableEnabled($definition['table'])
            && Schema::hasTable($definition['table'])
            && Schema::hasColumn($definition['table'], $definition['user_column'])
            && Schema::hasColumn($definition['table'], $definition['date_column']);
    }

    private function definitionBelongsToDepartment(array $definition): bool
    {
        return match ($definition['department_scope'] ?? 'users') {
            'services' => $this->department->services->isNotEmpty(),
            'investigations' => $this->department->investigationTypes->isNotEmpty(),
            default => $this->departmentUsers()->isNotEmpty(),
        };
    }

    private function baseQuery(array $definition)
    {
        [$startDate, $endDate] = $this->dateRange();
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
                ->where('services.department_id', $this->department->id);
        } elseif (($definition['department_scope'] ?? null) === 'investigations') {
            $query->join('investigations', "{$table}.investigation_id", '=', 'investigations.id')
                ->join('investigation_types', 'investigations.investigation_type_id', '=', 'investigation_types.id')
                ->where('investigation_types.department_id', $this->department->id);
        } else {
            $query->whereIn("{$table}.{$definition['user_column']}", $this->departmentUserIds());
        }

        if ($this->userId !== '') {
            $query->where("{$table}.{$definition['user_column']}", $this->userId);
        }

        if ($this->search !== '') {
            $this->applySearch($query, $definition);
        }

        return $query;
    }

    private function applySearch($query, array $definition): void
    {
        $search = $this->search;
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

    private function typeBreakdown(array $definitions): Collection
    {
        return collect($definitions)
            ->map(function ($definition, $key) {
                return (object) [
                    'key' => $key,
                    'label' => $definition['label'],
                    'count' => $this->baseQuery($definition)->count(),
                ];
            })
            ->filter(fn ($row) => $row->count > 0)
            ->sortByDesc('count')
            ->values();
    }

    private function userBreakdown(array $definitions, Collection $departmentUsers): Collection
    {
        $rows = collect();

        foreach ($definitions as $definition) {
            $table = $definition['table'];
            $rows = $rows->merge(
                $this->baseQuery($definition)
                    ->selectRaw("{$table}.{$definition['user_column']} as user_id, COALESCE(users.name, 'Unknown') as label, COUNT({$table}.id) as count")
                    ->groupBy("{$table}.{$definition['user_column']}", 'users.name')
                    ->get()
            );
        }

        $grouped = $rows->groupBy('user_id')->map(function ($userRows) {
            return (object) [
                'user_id' => $userRows->first()->user_id,
                'label' => $userRows->first()->label,
                'count' => (int) $userRows->sum('count'),
            ];
        });

        $seededUsers = $departmentUsers->mapWithKeys(fn ($user) => [
            $user->id => (object) [
                'user_id' => $user->id,
                'label' => $user->name,
                'count' => (int) ($grouped[$user->id]->count ?? 0),
            ],
        ]);

        return $seededUsers
            ->merge($grouped->reject(fn ($row, $userId) => $seededUsers->has($userId)))
            ->sortByDesc('count')
            ->values();
    }

    private function recentActivities(array $definitions): LengthAwarePaginator
    {
        $items = collect();

        foreach ($definitions as $definition) {
            $table = $definition['table'];
            $subjectSelect = ! empty($definition['subject_column'])
                ? "{$definition['subject_column']} as subject"
                : DB::raw("CONCAT('Record #', {$table}.id) as subject");

            $items = $items->merge(
                $this->baseQuery($definition)
                    ->select([
                        "{$table}.id",
                        "{$table}.{$definition['date_column']} as occurred_at",
                        "{$table}.{$definition['user_column']} as user_id",
                        'users.name as user_name',
                    ])
                    ->selectRaw('? as activity_label', [$definition['label']])
                    ->addSelect($subjectSelect)
                    ->orderByDesc("{$table}.{$definition['date_column']}")
                    ->limit(30)
                    ->get()
            );
        }

        $items = $items->sortByDesc('occurred_at')->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    private function chartPayload(Collection $typeBreakdown, Collection $userBreakdown): array
    {
        $rows = $this->chartBreakdown === 'users'
            ? $userBreakdown->filter(fn ($row) => $row->count > 0)->take(10)->values()
            : $typeBreakdown->take(10)->values();

        return [
            'type' => $this->chartType,
            'title' => $this->chartBreakdown === 'users' ? 'User Activity Breakdown' : 'Activity Type Breakdown',
            'labels' => $rows->pluck('label')->values(),
            'values' => $rows->pluck('count')->map(fn ($count) => (int) $count)->values(),
        ];
    }

    private function summary(Collection $typeBreakdown, Collection $userBreakdown): array
    {
        $serviceCount = $typeBreakdown
            ->whereIn('key', ['service_requests', 'completed_services'])
            ->sum('count');

        $investigationCount = $typeBreakdown
            ->whereIn('key', ['investigation_requests', 'completed_investigations'])
            ->sum('count');

        return [
            'total' => (int) $typeBreakdown->sum('count'),
            'service_count' => (int) $serviceCount,
            'investigation_count' => (int) $investigationCount,
            'clinical_count' => (int) max($typeBreakdown->sum('count') - $serviceCount - $investigationCount, 0),
            'active_users' => (int) $userBreakdown->where('count', '>', 0)->count(),
        ];
    }

    private function departmentUsers(): Collection
    {
        return User::where('department_id', $this->department->id)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function departmentUserIds(): array
    {
        return $this->departmentUsers()->pluck('id')->all();
    }

    private function dateRange(): array
    {
        if ($this->todayOnly) {
            return [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
        }

        $start = Carbon::parse($this->dateFrom ?: now()->startOfMonth()->format('Y-m-d'))->startOfDay();
        $end = Carbon::parse($this->dateTo ?: now()->format('Y-m-d'))->endOfDay();

        if ($start->gt($end)) {
            return [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    private function exportParameters(): array
    {
        return array_filter([
            'start_date' => $this->todayOnly ? null : $this->dateFrom,
            'end_date' => $this->todayOnly ? null : $this->dateTo,
            'today' => $this->todayOnly ? 1 : null,
            'user_id' => $this->userId ?: null,
            'activity_type' => $this->activityType ?: null,
            'search' => $this->search ?: null,
        ]);
    }
}

