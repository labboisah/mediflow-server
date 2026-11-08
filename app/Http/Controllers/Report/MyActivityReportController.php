<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MyActivityReportController extends Controller
{
    public function pdf(Request $request)
    {
        $definitions = $this->activeDefinitions($request);
        $activities = $this->activities($request, $definitions);
        $typeBreakdown = $this->typeBreakdown($activities);
        [$startDate, $endDate] = $this->dateRange($request);

        $summary = [
            'total' => $activities->count(),
            'activity_types' => $typeBreakdown->count(),
        ];

        $hospital = $this->hospitalHeaderData();
        $generatedBy = $request->user();

        $pdf = Pdf::loadView('reports.pdf.my-activity-report', compact(
            'activities',
            'typeBreakdown',
            'summary',
            'startDate',
            'endDate',
            'hospital',
            'generatedBy'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('my-activity-report-' . now()->format('Y-m-d') . '.pdf');
    }

    private function activities(Request $request, array $definitions): Collection
    {
        $items = collect();

        foreach ($definitions as $key => $definition) {
            $table = $definition['table'];
            $subjectSelect = ! empty($definition['subject_column'])
                ? "{$definition['subject_column']} as subject"
                : DB::raw("CONCAT('Record #', {$table}.id) as subject");

            $items = $items->merge(
                $this->baseQuery($request, $definition)
                    ->select([
                        "{$table}.id",
                        "{$table}.{$definition['date_column']} as occurred_at",
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

    private function activeDefinitions(Request $request): array
    {
        return collect($this->activityDefinitions())
            ->filter(fn ($definition, $key) => (! $request->filled('activity_type') || $request->input('activity_type') === $key) && $this->definitionIsAvailable($definition))
            ->all();
    }

    private function definitionIsAvailable(array $definition): bool
    {
        return app(\App\Services\LicenseService::class)->reportTableEnabled($definition['table'])
            && Schema::hasTable($definition['table'])
            && Schema::hasColumn($definition['table'], $definition['user_column'])
            && Schema::hasColumn($definition['table'], $definition['date_column']);
    }

    private function baseQuery(Request $request, array $definition)
    {
        [$startDate, $endDate] = $this->dateRange($request);
        $table = $definition['table'];

        $query = DB::table($table)
            ->where("{$table}.{$definition['user_column']}", $request->user()->id)
            ->whereBetween("{$table}.{$definition['date_column']}", [$startDate, $endDate]);

        foreach ($definition['joins'] ?? [] as $join) {
            $query->leftJoin(...$join);
        }

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull("{$table}.deleted_at");
        }

        return $query;
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
