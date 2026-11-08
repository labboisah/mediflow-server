<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class MyActivityReport extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $activityType = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public bool $todayOnly = false;
    public string $chartType = 'bar';

    public function mount(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
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
        $this->reset(['activityType', 'todayOnly']);
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function render()
    {
        $availableDefinitions = $this->availableDefinitions();
        $availableTypeBreakdown = $this->typeBreakdown($availableDefinitions);
        $definitions = $this->activeDefinitions($availableDefinitions);
        $typeBreakdown = $this->typeBreakdown($definitions);

        return view('components.reports.my-activity-report', [
            'user' => auth()->user(),
            'activityTypes' => $availableTypeBreakdown
                ->map(fn ($row) => [
                    'key' => $row->key,
                    'label' => $row->label,
                ])
                ->values(),
            'summary' => [
                'total' => (int) $typeBreakdown->sum('count'),
                'activity_types' => $typeBreakdown->count(),
            ],
            'typeBreakdown' => $typeBreakdown,
            'chartPayload' => $this->chartPayload($typeBreakdown),
            'activities' => $this->activities($definitions),
            'pdfUrl' => route('reports.my-activities.pdf', $this->exportParameters()),
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
                'joins' => [['services', 'service_requests.service_id', '=', 'services.id']],
                'subject_column' => 'services.name',
            ],
            'completed_services' => [
                'label' => 'Completed Services',
                'table' => 'service_requests',
                'user_column' => 'performed_by',
                'date_column' => 'completed_at',
                'joins' => [['services', 'service_requests.service_id', '=', 'services.id']],
                'subject_column' => 'services.name',
            ],
            'investigation_requests' => [
                'label' => 'Investigation Requests',
                'table' => 'investigation_requests',
                'user_column' => 'requested_by',
                'date_column' => 'requested_at',
                'joins' => [['investigations', 'investigation_requests.investigation_id', '=', 'investigations.id']],
                'subject_column' => 'investigations.name',
            ],
            'completed_investigations' => [
                'label' => 'Completed Investigations',
                'table' => 'investigation_requests',
                'user_column' => 'performed_by',
                'date_column' => 'completed_at',
                'joins' => [['investigations', 'investigation_requests.investigation_id', '=', 'investigations.id']],
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
            ->filter(fn ($definition) => $this->definitionIsAvailable($definition))
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

    private function baseQuery(array $definition)
    {
        [$startDate, $endDate] = $this->dateRange();
        $table = $definition['table'];

        $query = DB::table($table)
            ->where("{$table}.{$definition['user_column']}", auth()->id())
            ->whereBetween("{$table}.{$definition['date_column']}", [$startDate, $endDate]);

        foreach ($definition['joins'] ?? [] as $join) {
            $query->leftJoin(...$join);
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull("{$table}.deleted_at");
        }

        return $query;
    }

    private function typeBreakdown(array $definitions): Collection
    {
        return collect($definitions)
            ->map(fn ($definition, $key) => (object) [
                'key' => $key,
                'label' => $definition['label'],
                'count' => $this->baseQuery($definition)->count(),
            ])
            ->filter(fn ($row) => $row->count > 0)
            ->sortByDesc('count')
            ->values();
    }

    private function chartPayload(Collection $typeBreakdown): array
    {
        $rows = $typeBreakdown->take(10)->values();

        return [
            'type' => $this->chartType,
            'title' => 'My Activity Breakdown',
            'labels' => $rows->pluck('label')->values(),
            'values' => $rows->pluck('count')->map(fn ($count) => (int) $count)->values(),
        ];
    }

    private function activities(array $definitions): LengthAwarePaginator
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
                    ])
                    ->selectRaw('? as activity_label', [$definition['label']])
                    ->addSelect($subjectSelect)
                    ->orderByDesc("{$table}.{$definition['date_column']}")
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
            'activity_type' => $this->activityType ?: null,
        ]);
    }
}

