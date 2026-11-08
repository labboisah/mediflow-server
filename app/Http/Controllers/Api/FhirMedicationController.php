<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FhirResourceIdentity;
use App\Models\Medicine;
use App\Services\FhirResourceIdentityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class FhirMedicationController extends Controller
{
    private const PAGE_SIZE = 50;

    public function read(string $id, FhirResourceIdentityService $identities): JsonResponse
    {
        $medicine = $identities->medicationForId($id);
        if (! $medicine) {
            return $this->outcome('not-found', 'The requested Medication resource was not found.', 404);
        }

        return $this->fhirJson($this->toFhirMedication($medicine, $id));
    }

    public function search(Request $request, FhirResourceIdentityService $identities): JsonResponse
    {
        $allowed = ['_id', '_count', 'page', '_format'];
        $unsupported = array_diff(array_keys($request->query()), $allowed);
        if ($unsupported !== []) {
            return $this->outcome('not-supported', 'Unsupported search parameter: '.implode(', ', $unsupported), 400);
        }

        $validator = Validator::make($request->query(), [
            '_id' => ['sometimes', 'string', 'max:64', 'regex:/^[A-Za-z0-9.-]+$/'],
            '_count' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'between:1,100000'],
            '_format' => ['sometimes', 'in:json,application/fhir+json'],
        ]);
        if ($validator->fails()) {
            return $this->outcome('invalid', $validator->errors()->first(), 400);
        }

        $query = Medicine::query()->with('medicineType');
        $logicalId = $request->query('_id');
        if ($logicalId !== null) {
            $localKey = FhirResourceIdentity::query()
                ->where('resource_type', 'Medication')
                ->where('logical_id', $logicalId)
                ->value('local_key');
            if ($localKey === null) {
                return $this->searchBundle([], 0, $request, (int) $request->query('page', 1));
            }
            $query->whereKey($localKey);
        }

        $pageSize = min((int) $request->query('_count', self::PAGE_SIZE), 100);
        $page = (int) $request->query('page', 1);
        if (($page - 1) * $pageSize > 100000) {
            return $this->outcome('too-costly', 'The requested result page is beyond the supported offset range.', 400);
        }

        $medicines = $query->orderBy('medicines.id')->paginate($pageSize, ['medicines.*'], 'page', $page);
        $entries = $medicines->getCollection()->map(function (Medicine $medicine) use ($identities): array {
            $id = $identities->idForMedication($medicine);

            return [
                'fullUrl' => rtrim((string) config('fhir.base_url'), '/').'/Medication/'.$id,
                'resource' => $this->toFhirMedication($medicine, $id),
                'search' => ['mode' => 'match'],
            ];
        });

        return $this->searchBundle($entries, $medicines->total(), $request, $page, $medicines->hasMorePages());
    }

    private function toFhirMedication(Medicine $medicine, string $id): array
    {
        $codeText = collect([
            $medicine->name,
            $medicine->generic_name ? 'Generic: '.$medicine->generic_name : null,
            $medicine->strength ? 'Strength: '.$medicine->strength : null,
        ])->filter()->implode(' | ');
        $resource = [
            'resourceType' => 'Medication',
            'id' => $id,
            'code' => ['text' => $codeText],
        ];
        $form = $medicine->form ?: $medicine->medicineType?->name;
        if ($form) {
            $resource['form'] = ['text' => (string) $form];
        }

        if ($medicine->updated_at) {
            $resource['meta'] = ['lastUpdated' => $medicine->updated_at->toIso8601String()];
        }

        return $resource;
    }

    private function searchBundle(iterable $entries, int $total, Request $request, int $page, bool $hasMore = false): JsonResponse
    {
        $base = rtrim((string) config('fhir.base_url'), '/').'/Medication';
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
