<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FhirResourceIdentity;
use App\Models\PrescriptionItem;
use App\Services\FhirResourceIdentityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FhirMedicationRequestController extends Controller
{
    private const PAGE_SIZE = 50;

    public function read(string $id, FhirResourceIdentityService $identities): JsonResponse
    {
        $item = $identities->medicationRequestForId($id);
        if (! $item) {
            return $this->outcome('not-found', 'The requested MedicationRequest resource was not found.', 404);
        }

        return $this->fhirJson($this->toFhirMedicationRequest($item, $id, $identities));
    }

    public function search(Request $request, FhirResourceIdentityService $identities): JsonResponse
    {
        $allowed = ['_id', 'patient', 'subject', 'encounter', 'authoredon', 'status', '_count', 'page', '_format'];
        $unsupported = array_diff(array_keys($request->query()), $allowed);
        if ($unsupported !== []) {
            return $this->outcome('not-supported', 'Unsupported search parameter: '.implode(', ', $unsupported), 400);
        }

        $validator = Validator::make($request->query(), [
            '_id' => ['sometimes', 'string', 'max:64', 'regex:/^[A-Za-z0-9.-]+$/'],
            'patient' => ['sometimes', 'string', 'max:255'],
            'subject' => ['sometimes', 'string', 'max:255'],
            'encounter' => ['sometimes', 'string', 'max:255'],
            'authoredon' => ['sometimes', 'date_format:Y-m-d'],
            'status' => ['sometimes', 'in:active,on-hold,cancelled,completed,entered-in-error,stopped,draft,unknown'],
            '_count' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'between:1,100000'],
            '_format' => ['sometimes', 'in:json,application/fhir+json'],
        ]);
        if ($validator->fails()) {
            return $this->outcome('invalid', $validator->errors()->first(), 400);
        }

        $query = PrescriptionItem::query()
            ->whereHas('prescription.patientVisit.patient')
            ->whereHas('medicine')
            ->with([
                'medicine.medicineType',
                'route',
                'prescription.patientVisit.patient.demographic',
            ]);

        $logicalId = $request->query('_id');
        if ($logicalId !== null) {
            $localKey = FhirResourceIdentity::query()
                ->where('resource_type', 'MedicationRequest')
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
            $query->whereHas('prescription.patientVisit', fn (Builder $visit) => $visit->where('patient_id', $patientId));
        }

        $encounter = $request->query('encounter');
        if ($encounter !== null) {
            $visitId = $this->encounterIdFromReference($encounter);
            if ($visitId === null) {
                return $this->searchBundle([], 0, $request, (int) $request->query('page', 1));
            }
            $query->whereHas('prescription', fn (Builder $prescriptions) => $prescriptions->where('patient_visit_id', $visitId));
        }

        if ($request->query('authoredon') !== null) {
            $date = $request->query('authoredon');
            $query->whereHas('prescription', fn (Builder $prescriptions) => $prescriptions->whereDate('created_at', $date));
        }

        if ($request->query('status') !== null) {
            $this->applyStatusFilter($query, $request->query('status'));
        }

        $pageSize = min((int) $request->query('_count', self::PAGE_SIZE), 100);
        $page = (int) $request->query('page', 1);
        if (($page - 1) * $pageSize > 100000) {
            return $this->outcome('too-costly', 'The requested result page is beyond the supported offset range.', 400);
        }

        $items = $query->orderBy('prescription_items.id')->paginate($pageSize, ['prescription_items.*'], 'page', $page);
        $entries = $items->getCollection()->map(function (PrescriptionItem $item) use ($identities): array {
            $id = $identities->idForMedicationRequest($item);

            return [
                'fullUrl' => rtrim((string) config('fhir.base_url'), '/').'/MedicationRequest/'.$id,
                'resource' => $this->toFhirMedicationRequest($item, $id, $identities),
                'search' => ['mode' => 'match'],
            ];
        });

        return $this->searchBundle($entries, $items->total(), $request, $page, $items->hasMorePages());
    }

    private function applyStatusFilter(Builder $query, string $status): void
    {
        if ($status === 'active') {
            $query->where(function (Builder $items): void {
                $items->whereNull('medication_status')
                    ->orWhereRaw('LOWER(medication_status) = ?', ['started']);
            })->whereHas('prescription', fn (Builder $prescriptions) => $prescriptions->whereRaw(
                'LOWER(status) IN (?, ?, ?)',
                ['active', 'submitted', 'paid'],
            ));

            return;
        }

        if ($status === 'stopped') {
            $query->whereRaw('LOWER(medication_status) = ?', ['stopped']);

            return;
        }

        if (in_array($status, ['completed', 'cancelled', 'entered-in-error'], true)) {
            $localStatuses = match ($status) {
                'cancelled' => ['cancelled', 'canceled'],
                default => [$status],
            };
            $query->whereHas('prescription', fn (Builder $prescriptions) => $prescriptions->whereIn(
                DB::raw('LOWER(status)'),
                $localStatuses,
            ))->where(function (Builder $items): void {
                $items->whereNull('medication_status')
                    ->orWhereRaw('LOWER(medication_status) != ?', ['stopped']);
            });

            return;
        }

        if (in_array($status, ['on-hold', 'draft'], true)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $unknown): void {
            $unknown->where(function (Builder $items): void {
                $items->whereNotNull('medication_status')
                    ->whereRaw('LOWER(medication_status) NOT IN (?, ?)', ['started', 'stopped']);
            })->orWhere(function (Builder $localStates): void {
                $localStates->where(function (Builder $items): void {
                    $items->whereNull('medication_status')
                        ->orWhereRaw('LOWER(medication_status) != ?', ['stopped']);
                })->whereHas('prescription', fn (Builder $prescriptions) => $prescriptions
                    ->whereNull('status')
                    ->orWhereRaw('LOWER(status) NOT IN (?, ?, ?, ?, ?, ?, ?)', [
                        'active',
                        'submitted',
                        'paid',
                        'completed',
                        'cancelled',
                        'canceled',
                        'entered-in-error',
                    ]));
            });
        });
    }

    private function toFhirMedicationRequest(PrescriptionItem $item, string $id, FhirResourceIdentityService $identities): array
    {
        $prescription = $item->prescription;
        $visit = $prescription->patientVisit;
        $localStatus = strtolower(trim((string) $item->medication_status));
        $prescriptionStatus = strtolower(trim((string) $prescription->status));
        $status = $localStatus === 'stopped'
            ? 'stopped'
            : match ($prescriptionStatus) {
                'active', 'submitted', 'paid' => 'active',
                'completed' => 'completed',
                'cancelled', 'canceled' => 'cancelled',
                'entered-in-error' => 'entered-in-error',
                default => 'unknown',
            };
        $resource = [
            'resourceType' => 'MedicationRequest',
            'id' => $id,
            'status' => $status,
            'intent' => 'order',
            'medicationReference' => [
                'reference' => 'Medication/'.$identities->idForMedication($item->medicine),
            ],
            'subject' => [
                'reference' => 'Patient/'.$identities->idForPatient($visit->patient),
            ],
        ];

        if ($visit) {
            $resource['encounter'] = [
                'reference' => 'Encounter/'.$identities->idForEncounter($visit),
            ];
        }
        if ($prescription->created_at) {
            $resource['authoredOn'] = $prescription->created_at->toIso8601String();
        }
        if ($prescription->treatment_diagnosis) {
            $resource['reasonCode'] = [['text' => (string) $prescription->treatment_diagnosis]];
        }

        $instructions = array_filter([
            $item->dosage ? 'Dose: '.$item->dosage : null,
            $item->period ? 'Period: '.$item->period : null,
            $item->duration ? 'Duration: '.$item->duration : null,
            $item->route?->name ? 'Route: '.$item->route->name : null,
        ]);
        if ($instructions !== []) {
            $resource['dosageInstruction'] = [['text' => implode('; ', $instructions)]];
        }
        if ($item->updated_at) {
            $resource['meta'] = ['lastUpdated' => $item->updated_at->toIso8601String()];
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
        $base = rtrim((string) config('fhir.base_url'), '/').'/MedicationRequest';
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
