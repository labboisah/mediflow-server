<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FhirResourceIdentity;
use App\Models\PatientVisit;
use App\Services\FhirResourceIdentityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class FhirEncounterController extends Controller
{
    private const PAGE_SIZE = 50;

    public function read(string $id, FhirResourceIdentityService $identities): JsonResponse
    {
        $visit = $identities->encounterForId($id);
        if (! $visit) {
            return $this->outcome('not-found', 'The requested Encounter resource was not found.', 404);
        }

        return $this->fhirJson($this->toFhirEncounter($visit, $id, $identities));
    }

    public function search(Request $request, FhirResourceIdentityService $identities): JsonResponse
    {
        $allowed = ['_id', 'patient', 'subject', 'date', 'status', '_count', 'page', '_format'];
        $unsupported = array_diff(array_keys($request->query()), $allowed);
        if ($unsupported !== []) {
            return $this->outcome('not-supported', 'Unsupported search parameter: '.implode(', ', $unsupported), 400);
        }

        $validator = Validator::make($request->query(), [
            '_id' => ['sometimes', 'string', 'max:64', 'regex:/^[A-Za-z0-9.-]+$/'],
            'patient' => ['sometimes', 'string', 'max:255'],
            'subject' => ['sometimes', 'string', 'max:255'],
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'status' => ['sometimes', 'in:in-progress,finished,unknown'],
            '_count' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'between:1,100000'],
            '_format' => ['sometimes', 'in:json,application/fhir+json'],
        ]);
        if ($validator->fails()) {
            return $this->outcome('invalid', $validator->errors()->first(), 400);
        }

        $query = PatientVisit::query()
            ->whereHas('patient')
            ->with(['patient.demographic'])
            ->withExists('admissions');

        $logicalId = $request->query('_id');
        if ($logicalId !== null) {
            $localKey = FhirResourceIdentity::query()
                ->where('resource_type', 'Encounter')
                ->where('logical_id', $logicalId)
                ->value('local_key');
            if ($localKey === null) {
                return $this->searchBundle([], 0, $request, (int) $request->query('page', 1));
            }
            $query->whereKey($localKey);
        }

        foreach (['patient', 'subject'] as $parameter) {
            $patientReference = $request->query($parameter);
            if ($patientReference === null) {
                continue;
            }
            $patientId = $this->patientIdFromReference($patientReference);
            if ($patientId === null) {
                return $this->searchBundle([], 0, $request, (int) $request->query('page', 1));
            }
            $query->where('patient_id', $patientId);
        }

        if ($request->query('date') !== null) {
            $query->whereDate('visit_date', $request->query('date'));
        }

        $status = $request->query('status');
        if ($status === 'in-progress') {
            $query->whereRaw('LOWER(status) IN (?, ?)', ['active', 'admitted']);
        } elseif ($status === 'finished') {
            $query->whereRaw('LOWER(status) IN (?, ?, ?)', ['transferred', 'discharged', 'closed']);
        } elseif ($status === 'unknown') {
            $query->where(function (Builder $statuses): void {
                $statuses->whereNull('status')
                    ->orWhereRaw('LOWER(status) NOT IN (?, ?, ?, ?, ?)', [
                        'active',
                        'admitted',
                        'transferred',
                        'discharged',
                        'closed',
                    ]);
            });
        }

        $pageSize = min((int) $request->query('_count', self::PAGE_SIZE), 100);
        $page = (int) $request->query('page', 1);
        if (($page - 1) * $pageSize > 100000) {
            return $this->outcome('too-costly', 'The requested result page is beyond the supported offset range.', 400);
        }

        $visits = $query->orderBy('patient_visits.id')->paginate($pageSize, ['patient_visits.*'], 'page', $page);
        $entries = $visits->getCollection()->map(function (PatientVisit $visit) use ($identities): array {
            $id = $identities->idForEncounter($visit);

            return [
                'fullUrl' => rtrim((string) config('fhir.base_url'), '/').'/Encounter/'.$id,
                'resource' => $this->toFhirEncounter($visit, $id, $identities),
                'search' => ['mode' => 'match'],
            ];
        });

        return $this->searchBundle($entries, $visits->total(), $request, $page, $visits->hasMorePages());
    }

    private function toFhirEncounter(PatientVisit $visit, string $id, FhirResourceIdentityService $identities): array
    {
        $status = strtolower((string) $visit->status);
        $resource = [
            'resourceType' => 'Encounter',
            'id' => $id,
            'status' => match ($status) {
                'active', 'admitted' => 'in-progress',
                'transferred', 'discharged', 'closed' => 'finished',
                default => 'unknown',
            },
            'class' => [
                'system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => $visit->admissions_exists || $status === 'admitted' ? 'IMP' : 'AMB',
                'display' => $visit->admissions_exists || $status === 'admitted' ? 'inpatient encounter' : 'ambulatory',
            ],
            'subject' => [
                'reference' => 'Patient/'.$identities->idForPatient($visit->patient),
            ],
            'period' => [
                'start' => $visit->visit_date?->toIso8601String(),
            ],
        ];

        if ($visit->visit_type) {
            $resource['type'] = [['text' => (string) $visit->visit_type]];
        }
        if ($visit->reason_for_visit) {
            $resource['reasonCode'] = [['text' => (string) $visit->reason_for_visit]];
        }
        if ($visit->updated_at) {
            $resource['meta'] = ['lastUpdated' => $visit->updated_at->toIso8601String()];
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

    private function searchBundle(iterable $entries, int $total, Request $request, int $page, bool $hasMore = false): JsonResponse
    {
        $base = rtrim((string) config('fhir.base_url'), '/').'/Encounter';
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
            'issue' => [['severity' => 'error', 'code' => $code, 'diagnostics' => $diagnostics]],
        ], $status);
    }
}
