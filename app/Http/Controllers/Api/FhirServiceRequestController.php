<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FhirResourceIdentity;
use App\Models\InvestigationRequest;
use App\Models\ServiceRequest as LocalServiceRequest;
use App\Services\FhirResourceIdentityService;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FhirServiceRequestController extends Controller
{
    private const PAGE_SIZE = 50;

    public function read(string $id, FhirResourceIdentityService $identities): JsonResponse
    {
        $request = $identities->serviceRequestForId($id);
        if (! $request) {
            return $this->outcome('not-found', 'The requested ServiceRequest resource was not found.', 404);
        }

        return $this->fhirJson($this->toFhirServiceRequest($request, $id, $identities));
    }

    public function search(Request $request, FhirResourceIdentityService $identities): JsonResponse
    {
        $allowed = ['_id', 'patient', 'subject', 'encounter', 'authored', 'status', '_count', 'page', '_format'];
        $unsupported = array_diff(array_keys($request->query()), $allowed);
        if ($unsupported !== []) {
            return $this->outcome('not-supported', 'Unsupported search parameter: '.implode(', ', $unsupported), 400);
        }

        $validator = Validator::make($request->query(), [
            '_id' => ['sometimes', 'string', 'max:64', 'regex:/^[A-Za-z0-9.-]+$/'],
            'patient' => ['sometimes', 'string', 'max:255'],
            'subject' => ['sometimes', 'string', 'max:255'],
            'encounter' => ['sometimes', 'string', 'max:255'],
            'authored' => ['sometimes', 'date_format:Y-m-d'],
            'status' => ['sometimes', 'in:active,completed,unknown'],
            '_count' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'between:1,100000'],
            '_format' => ['sometimes', 'in:json,application/fhir+json'],
        ]);
        if ($validator->fails()) {
            return $this->outcome('invalid', $validator->errors()->first(), 400);
        }

        $query = DB::query()->fromSub($this->resourceQuery(), 'fhir_service_requests');
        $logicalId = $request->query('_id');
        if ($logicalId !== null) {
            $localKey = FhirResourceIdentity::query()
                ->where('resource_type', 'ServiceRequest')
                ->where('logical_id', $logicalId)
                ->value('local_key');
            if (! $localKey || ! preg_match('/^(service_request|investigation_request):(\d+)$/', $localKey, $matches)) {
                return $this->searchBundle([], 0, $request, (int) $request->query('page', 1));
            }
            $query->where('source_type', $matches[1])->where('id', $matches[2]);
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
            $query->whereIn('patient_visit_id', DB::table('patient_visits')
                ->select('id')
                ->where('patient_id', $patientId)
                ->whereNull('deleted_at'));
        }

        $encounter = $request->query('encounter');
        if ($encounter !== null) {
            $visitId = $this->encounterIdFromReference($encounter);
            if ($visitId === null) {
                return $this->searchBundle([], 0, $request, (int) $request->query('page', 1));
            }
            $query->where('patient_visit_id', $visitId);
        }

        if ($request->query('authored') !== null) {
            $query->whereDate('requested_at', $request->query('authored'));
        }

        $status = $request->query('status');
        if ($status === 'active') {
            $query->whereRaw('LOWER(status) IN (?, ?, ?)', ['pending', 'active', 'requested']);
        } elseif ($status === 'completed') {
            $query->whereRaw('LOWER(status) = ?', ['completed']);
        } elseif ($status === 'unknown') {
            $query->where(function (QueryBuilder $statuses): void {
                $statuses->whereNull('status')
                    ->orWhereRaw('LOWER(status) NOT IN (?, ?, ?, ?)', ['pending', 'active', 'requested', 'completed']);
            });
        }

        $pageSize = min((int) $request->query('_count', self::PAGE_SIZE), 100);
        $page = (int) $request->query('page', 1);
        if (($page - 1) * $pageSize > 100000) {
            return $this->outcome('too-costly', 'The requested result page is beyond the supported offset range.', 400);
        }

        $total = (clone $query)->count();
        $rows = $query->orderByDesc('requested_at')
            ->orderBy('source_type')
            ->orderBy('id')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        $serviceIds = $rows->where('source_type', 'service_request')->pluck('id');
        $investigationIds = $rows->where('source_type', 'investigation_request')->pluck('id');
        $serviceRequests = LocalServiceRequest::query()
            ->with(['service', 'patientVisit.patient.demographic'])
            ->whereIn('id', $serviceIds)
            ->get()
            ->keyBy('id');
        $investigationRequests = InvestigationRequest::query()
            ->with(['investigation', 'patientVisit.patient.demographic'])
            ->whereIn('id', $investigationIds)
            ->get()
            ->keyBy('id');

        $entries = $rows->map(function (object $row) use ($identities, $serviceRequests, $investigationRequests): array {
            $localRequest = $row->source_type === 'service_request'
                ? $serviceRequests->get($row->id)
                : $investigationRequests->get($row->id);
            if (! $localRequest) {
                return [];
            }

            $id = $identities->idForServiceRequest($localRequest);

            return [
                'fullUrl' => rtrim((string) config('fhir.base_url'), '/').'/ServiceRequest/'.$id,
                'resource' => $this->toFhirServiceRequest($localRequest, $id, $identities),
                'search' => ['mode' => 'match'],
            ];
        })->filter();

        return $this->searchBundle($entries, $total, $request, $page, ($page * $pageSize) < $total);
    }

    private function resourceQuery(): QueryBuilder
    {
        $serviceRequests = DB::table('service_requests')
            ->selectRaw("'service_request' as source_type, id, patient_visit_id, service_id as item_id, clinical_diagnoses, requested_at, completed_at, status")
            ->whereNull('deleted_at')
            ->whereNotNull('patient_visit_id')
            ->whereExists(function (QueryBuilder $patients): void {
                $patients->selectRaw('1')
                    ->from('patient_visits')
                    ->join('patients', 'patients.id', '=', 'patient_visits.patient_id')
                    ->whereColumn('patient_visits.id', 'service_requests.patient_visit_id')
                    ->whereNull('patient_visits.deleted_at')
                    ->whereNull('patients.deleted_at');
            });

        $investigationRequests = DB::table('investigation_requests')
            ->selectRaw("'investigation_request' as source_type, id, patient_visit_id, investigation_id as item_id, clinical_diagnoses, requested_at, completed_at, status")
            ->whereNull('deleted_at')
            ->whereNotNull('patient_visit_id')
            ->whereExists(function (QueryBuilder $patients): void {
                $patients->selectRaw('1')
                    ->from('patient_visits')
                    ->join('patients', 'patients.id', '=', 'patient_visits.patient_id')
                    ->whereColumn('patient_visits.id', 'investigation_requests.patient_visit_id')
                    ->whereNull('patient_visits.deleted_at')
                    ->whereNull('patients.deleted_at');
            });

        return $serviceRequests->unionAll($investigationRequests);
    }

    private function toFhirServiceRequest(LocalServiceRequest|InvestigationRequest $request, string $id, FhirResourceIdentityService $identities): array
    {
        $patientVisit = $request->patientVisit;
        $itemName = $request instanceof InvestigationRequest
            ? $request->investigation?->name
            : $request->service?->name;
        $localStatus = strtolower(trim((string) $request->status));
        $resource = [
            'resourceType' => 'ServiceRequest',
            'id' => $id,
            'status' => match ($localStatus) {
                'pending', 'active', 'requested' => 'active',
                'completed' => 'completed',
                default => 'unknown',
            },
            'intent' => 'order',
            'code' => ['text' => (string) ($itemName ?: 'Service request')],
            'subject' => [
                'reference' => 'Patient/'.$identities->idForPatient($patientVisit->patient),
            ],
        ];

        if ($patientVisit) {
            $resource['encounter'] = [
                'reference' => 'Encounter/'.$identities->idForEncounter($patientVisit),
            ];
        }
        if ($request->requested_at) {
            $resource['authoredOn'] = $request->requested_at->toIso8601String();
        }
        if ($request->clinical_diagnoses) {
            $resource['reasonCode'] = [['text' => (string) $request->clinical_diagnoses]];
        }
        if ($request->updated_at) {
            $resource['meta'] = ['lastUpdated' => $request->updated_at->toIso8601String()];
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
        $base = rtrim((string) config('fhir.base_url'), '/').'/ServiceRequest';
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
