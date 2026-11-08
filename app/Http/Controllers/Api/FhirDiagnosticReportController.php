<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiagnosticReport;
use App\Models\DiagnosticReportRevision;
use App\Models\FhirResourceIdentity;
use App\Services\FhirResourceIdentityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FhirDiagnosticReportController extends Controller
{
    private const PAGE_SIZE = 50;

    public function read(string $id, FhirResourceIdentityService $identities): JsonResponse
    {
        $report = $identities->diagnosticReportForId($id);
        if (! $report) {
            return $this->outcome('not-found', 'The requested DiagnosticReport resource was not found.', 404);
        }

        $this->addLatestRevisionAction($report);

        return $this->fhirJson($this->toFhirDiagnosticReport($report, $id, $identities));
    }

    public function search(Request $request, FhirResourceIdentityService $identities): JsonResponse
    {
        $allowed = ['_id', 'patient', 'subject', 'encounter', 'date', 'status', '_count', 'page', '_format'];
        $unsupported = array_diff(array_keys($request->query()), $allowed);
        if ($unsupported !== []) {
            return $this->outcome('not-supported', 'Unsupported search parameter: '.implode(', ', $unsupported), 400);
        }

        $validator = Validator::make($request->query(), [
            '_id' => ['sometimes', 'string', 'max:64', 'regex:/^[A-Za-z0-9.-]+$/'],
            'patient' => ['sometimes', 'string', 'max:255'],
            'subject' => ['sometimes', 'string', 'max:255'],
            'encounter' => ['sometimes', 'string', 'max:255'],
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'status' => ['sometimes', 'in:preliminary,final,amended,unknown'],
            '_count' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'between:1,100000'],
            '_format' => ['sometimes', 'in:json,application/fhir+json'],
        ]);
        if ($validator->fails()) {
            return $this->outcome('invalid', $validator->errors()->first(), 400);
        }

        $query = DiagnosticReport::query()
            ->whereHas('investigationRequest.patientVisit.patient')
            ->with([
                'investigationRequest.investigation',
                'investigationRequest.patientVisit.patient.demographic',
            ])
            ->addSelect(['latest_revision_action' => DiagnosticReportRevision::query()
                ->select('action')
                ->whereColumn('diagnostic_report_id', 'diagnostic_reports.id')
                ->orderByDesc('version')
                ->limit(1)]);

        $logicalId = $request->query('_id');
        if ($logicalId !== null) {
            $localKey = FhirResourceIdentity::query()
                ->where('resource_type', 'DiagnosticReport')
                ->where('logical_id', $logicalId)
                ->value('local_key');
            if ($localKey === null) {
                return $this->searchBundle([], 0, $request, (int) $request->query('page', 1));
            }
            $query->whereKey($localKey);
        }

        foreach (['patient', 'subject'] as $parameter) {
            $reference = $request->query($parameter);
            if ($reference === null) {
                continue;
            }
            $patientId = $this->patientIdFromReference($reference);
            if ($patientId === null) {
                return $this->searchBundle([], 0, $request, (int) $request->query('page', 1));
            }
            $query->whereHas('investigationRequest.patientVisit', fn (Builder $visit) => $visit->where('patient_id', $patientId));
        }

        $encounter = $request->query('encounter');
        if ($encounter !== null) {
            $visitId = $this->encounterIdFromReference($encounter);
            if ($visitId === null) {
                return $this->searchBundle([], 0, $request, (int) $request->query('page', 1));
            }
            $query->whereHas('investigationRequest', fn (Builder $requests) => $requests->where('patient_visit_id', $visitId));
        }

        if ($request->query('date') !== null) {
            $date = $request->query('date');
            $query->where(function (Builder $dates) use ($date): void {
                $dates->whereDate('performed_at', $date)
                    ->orWhereDate('reported_at', $date);
            });
        }

        $status = $request->query('status');
        if ($status === 'preliminary') {
            $query->whereRaw('LOWER(diagnostic_reports.status) = ?', ['performed']);
        } elseif ($status === 'final') {
            $query->whereRaw('LOWER(diagnostic_reports.status) = ?', ['reported'])
                ->whereNotExists($this->latestAmendmentQuery());
        } elseif ($status === 'amended') {
            $query->whereRaw('LOWER(diagnostic_reports.status) = ?', ['reported'])
                ->whereExists($this->latestAmendmentQuery());
        } elseif ($status === 'unknown') {
            $query->where(function (Builder $statuses): void {
                $statuses->whereNull('diagnostic_reports.status')
                    ->orWhereRaw('LOWER(diagnostic_reports.status) NOT IN (?, ?)', ['performed', 'reported']);
            });
        }

        $pageSize = min((int) $request->query('_count', self::PAGE_SIZE), 100);
        $page = (int) $request->query('page', 1);
        if (($page - 1) * $pageSize > 100000) {
            return $this->outcome('too-costly', 'The requested result page is beyond the supported offset range.', 400);
        }

        $reports = $query->orderBy('diagnostic_reports.id')->paginate($pageSize, ['*'], 'page', $page);
        $entries = $reports->getCollection()->map(function (DiagnosticReport $report) use ($identities): array {
            $id = $identities->idForDiagnosticReport($report);

            return [
                'fullUrl' => rtrim((string) config('fhir.base_url'), '/').'/DiagnosticReport/'.$id,
                'resource' => $this->toFhirDiagnosticReport($report, $id, $identities),
                'search' => ['mode' => 'match'],
            ];
        });

        return $this->searchBundle($entries, $reports->total(), $request, $page, $reports->hasMorePages());
    }

    private function addLatestRevisionAction(DiagnosticReport $report): void
    {
        $report->setAttribute('latest_revision_action', DiagnosticReportRevision::query()
            ->where('diagnostic_report_id', $report->getKey())
            ->orderByDesc('version')
            ->value('action'));
    }

    private function latestAmendmentQuery(): QueryBuilder
    {
        return DB::table('diagnostic_report_revisions')
            ->selectRaw('1')
            ->whereColumn('diagnostic_report_id', 'diagnostic_reports.id')
            ->whereColumn('version', 'diagnostic_reports.version')
            ->where('action', 'amended');
    }

    private function toFhirDiagnosticReport(DiagnosticReport $report, string $id, FhirResourceIdentityService $identities): array
    {
        $request = $report->investigationRequest;
        $visit = $request->patientVisit;
        $localStatus = strtolower(trim((string) $report->status));
        $status = match ($localStatus) {
            'performed' => 'preliminary',
            'reported' => $report->latest_revision_action === 'amended' ? 'amended' : 'final',
            default => 'unknown',
        };
        $resource = [
            'resourceType' => 'DiagnosticReport',
            'id' => $id,
            'status' => $status,
            'code' => ['text' => (string) ($request->investigation?->name ?: 'Diagnostic report')],
            'subject' => [
                'reference' => 'Patient/'.$identities->idForPatient($visit->patient),
            ],
            'basedOn' => [[
                'reference' => 'ServiceRequest/'.$identities->idForServiceRequest($request),
            ]],
        ];

        if ($visit) {
            $resource['encounter'] = [
                'reference' => 'Encounter/'.$identities->idForEncounter($visit),
            ];
        }
        if ($report->performed_at) {
            $resource['effectiveDateTime'] = $report->performed_at->toIso8601String();
        }
        if ($report->reported_at || $report->performed_at) {
            $resource['issued'] = ($report->reported_at ?? $report->performed_at)->toIso8601String();
        }
        if ($report->impression) {
            $resource['conclusion'] = (string) $report->impression;
        }

        $sections = array_filter([
            $report->clinical_note ? 'Clinical note: '.$report->clinical_note : null,
            $report->findings ? 'Findings: '.$report->findings : null,
            $report->impression ? 'Impression: '.$report->impression : null,
        ]);
        if ($sections !== []) {
            $resource['presentedForm'] = [[
                'contentType' => 'text/plain',
                'data' => base64_encode(implode("\n\n", $sections)),
                'title' => 'Diagnostic report',
                ...($report->reported_at ? ['creation' => $report->reported_at->toIso8601String()] : []),
            ]];
        }
        if ($report->updated_at) {
            $resource['meta'] = ['lastUpdated' => $report->updated_at->toIso8601String()];
        }

        return $resource;
    }

    private function patientIdFromReference(string $reference): ?string
    {
        if (! preg_match('#(?:^|/)Patient/([A-Za-z0-9.-]{1,64})$#', $reference, $matches)) {
            return null;
        }

        return FhirResourceIdentity::query()
            ->where('resource_type', 'Patient')
            ->where('logical_id', $matches[1])
            ->value('local_key');
    }

    private function encounterIdFromReference(string $reference): ?string
    {
        if (! preg_match('#(?:^|/)Encounter/([A-Za-z0-9.-]{1,64})$#', $reference, $matches)) {
            return null;
        }

        return FhirResourceIdentity::query()
            ->where('resource_type', 'Encounter')
            ->where('logical_id', $matches[1])
            ->value('local_key');
    }

    private function searchBundle(iterable $entries, int $total, Request $request, int $page, bool $hasMore = false): JsonResponse
    {
        $base = rtrim((string) config('fhir.base_url'), '/').'/DiagnosticReport';
        $query = $request->query();
        $query['page'] = $page;
        $links = [['relation' => 'self', 'url' => $base.'?'.Arr::query($query)]];
        if ($hasMore) {
            $query['page'] = $page + 1;
            $links[] = ['relation' => 'next', 'url' => $base.'?'.Arr::query($query)];
        }

        return $this->fhirJson([
            'resourceType' => 'Bundle',
            'type' => 'searchset',
            'total' => $total,
            'link' => $links,
            'entry' => collect($entries)->values()->all(),
        ]);
    }

    private function fhirJson(array $resource, int $status = 200): JsonResponse
    {
        return response()->json($resource, $status, [
            'Content-Type' => 'application/fhir+json; charset=utf-8',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function outcome(string $code, string $diagnostics, int $status): JsonResponse
    {
        return $this->fhirJson([
            'resourceType' => 'OperationOutcome',
            'issue' => [[
                'severity' => 'error',
                'code' => $code,
                'diagnostics' => $diagnostics,
            ]],
        ], $status);
    }
}
