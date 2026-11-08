<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FhirResourceIdentity;
use App\Models\Patient;
use App\Services\FhirResourceIdentityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FhirPatientController extends Controller
{
    private const PAGE_SIZE = 50;

    public function metadata(): JsonResponse
    {
        if (! config('fhir.enabled')) {
            return $this->outcome('not-found', 'FHIR API is not enabled.', 404);
        }

        $baseUrl = rtrim((string) config('fhir.base_url'), '/');

        return $this->fhirJson([
            'resourceType' => 'CapabilityStatement',
            'status' => 'active',
            'date' => now()->toIso8601String(),
            'kind' => 'instance',
            'software' => [
                'name' => (string) config('app.name'),
                'version' => (string) config('app.version', 'unknown'),
            ],
            'implementation' => [
                'description' => 'MediFlow read-only FHIR R4 adapter.',
                'url' => $baseUrl,
            ],
            'fhirVersion' => config('fhir.version', '4.0.1'),
            'format' => ['application/fhir+json'],
            'rest' => [[
                'mode' => 'server',
                'security' => [
                    'description' => 'Each resource requires a Sanctum bearer token with that resource-specific FHIR read ability and an authenticated MediFlow user with the corresponding existing resource-read permission. This endpoint is not SMART on FHIR.',
                ],
                'searchParam' => [
                    ['name' => '_count', 'type' => 'number', 'documentation' => 'Page size from 1 through 100.'],
                ],
                'resource' => [
                    [
                        'type' => 'Patient',
                        'profile' => 'http://hl7.org/fhir/StructureDefinition/Patient',
                        'interaction' => [
                            ['code' => 'read'],
                            ['code' => 'search-type'],
                        ],
                        'searchParam' => [
                            ['name' => '_id', 'type' => 'token', 'documentation' => 'FHIR Patient logical id.'],
                            ['name' => 'identifier', 'type' => 'token', 'documentation' => 'Hospital number; optionally system|value.'],
                            ['name' => 'name', 'type' => 'string', 'documentation' => 'Prefix match against given and family names.'],
                            ['name' => 'family', 'type' => 'string', 'documentation' => 'Prefix match against family name.'],
                            ['name' => 'given', 'type' => 'string', 'documentation' => 'Prefix match against given name.'],
                            ['name' => 'gender', 'type' => 'token', 'documentation' => 'FHIR administrative gender.'],
                            ['name' => 'birthdate', 'type' => 'date', 'documentation' => 'Exact YYYY-MM-DD date of birth.'],
                        ],
                    ],
                    [
                        'type' => 'Encounter',
                        'profile' => 'http://hl7.org/fhir/StructureDefinition/Encounter',
                        'interaction' => [
                            ['code' => 'read'],
                            ['code' => 'search-type'],
                        ],
                        'searchParam' => [
                            ['name' => '_id', 'type' => 'token', 'documentation' => 'FHIR Encounter logical id.'],
                            ['name' => 'patient', 'type' => 'reference', 'documentation' => 'Patient reference.'],
                            ['name' => 'subject', 'type' => 'reference', 'documentation' => 'Patient subject reference.'],
                            ['name' => 'date', 'type' => 'date', 'documentation' => 'Exact YYYY-MM-DD visit date.'],
                            ['name' => 'status', 'type' => 'token', 'documentation' => 'FHIR Encounter status.'],
                        ],
                    ],
                    [
                        'type' => 'Observation',
                        'profile' => 'http://hl7.org/fhir/StructureDefinition/Observation',
                        'interaction' => [
                            ['code' => 'read'],
                            ['code' => 'search-type'],
                        ],
                        'searchParam' => [
                            ['name' => '_id', 'type' => 'token', 'documentation' => 'FHIR Observation logical id.'],
                            ['name' => 'patient', 'type' => 'reference', 'documentation' => 'Patient reference.'],
                            ['name' => 'subject', 'type' => 'reference', 'documentation' => 'Patient subject reference.'],
                            ['name' => 'date', 'type' => 'date', 'documentation' => 'Exact YYYY-MM-DD measurement date.'],
                        ],
                    ],
                    [
                        'type' => 'ServiceRequest',
                        'profile' => 'http://hl7.org/fhir/StructureDefinition/ServiceRequest',
                        'interaction' => [
                            ['code' => 'read'],
                            ['code' => 'search-type'],
                        ],
                        'searchParam' => [
                            ['name' => '_id', 'type' => 'token', 'documentation' => 'FHIR ServiceRequest logical id.'],
                            ['name' => 'patient', 'type' => 'reference', 'documentation' => 'Patient reference.'],
                            ['name' => 'subject', 'type' => 'reference', 'documentation' => 'Patient subject reference.'],
                            ['name' => 'encounter', 'type' => 'reference', 'documentation' => 'Encounter reference.'],
                            ['name' => 'authored', 'type' => 'date', 'documentation' => 'Exact YYYY-MM-DD request date.'],
                            ['name' => 'status', 'type' => 'token', 'documentation' => 'FHIR status: active, completed, or unknown.'],
                        ],
                    ],
                    [
                        'type' => 'DiagnosticReport',
                        'profile' => 'http://hl7.org/fhir/StructureDefinition/DiagnosticReport',
                        'interaction' => [
                            ['code' => 'read'],
                            ['code' => 'search-type'],
                        ],
                        'searchParam' => [
                            ['name' => '_id', 'type' => 'token', 'documentation' => 'FHIR DiagnosticReport logical id.'],
                            ['name' => 'patient', 'type' => 'reference', 'documentation' => 'Patient reference.'],
                            ['name' => 'subject', 'type' => 'reference', 'documentation' => 'Patient subject reference.'],
                            ['name' => 'encounter', 'type' => 'reference', 'documentation' => 'Encounter reference.'],
                            ['name' => 'date', 'type' => 'date', 'documentation' => 'Exact YYYY-MM-DD performed or reported date.'],
                            ['name' => 'status', 'type' => 'token', 'documentation' => 'FHIR diagnostic report status.'],
                        ],
                    ],
                    [
                        'type' => 'Medication',
                        'profile' => 'http://hl7.org/fhir/StructureDefinition/Medication',
                        'interaction' => [
                            ['code' => 'read'],
                            ['code' => 'search-type'],
                        ],
                        'searchParam' => [
                            ['name' => '_id', 'type' => 'token', 'documentation' => 'FHIR Medication logical id.'],
                        ],
                    ],
                    [
                        'type' => 'MedicationRequest',
                        'profile' => 'http://hl7.org/fhir/StructureDefinition/MedicationRequest',
                        'interaction' => [
                            ['code' => 'read'],
                            ['code' => 'search-type'],
                        ],
                        'searchParam' => [
                            ['name' => '_id', 'type' => 'token', 'documentation' => 'FHIR MedicationRequest logical id.'],
                            ['name' => 'patient', 'type' => 'reference', 'documentation' => 'Patient reference.'],
                            ['name' => 'subject', 'type' => 'reference', 'documentation' => 'Patient subject reference.'],
                            ['name' => 'encounter', 'type' => 'reference', 'documentation' => 'Encounter reference.'],
                            ['name' => 'authoredon', 'type' => 'date', 'documentation' => 'Exact YYYY-MM-DD prescription date.'],
                            ['name' => 'status', 'type' => 'token', 'documentation' => 'FHIR MedicationRequest status.'],
                        ],
                    ],
                    [
                        'type' => 'MedicationDispense',
                        'profile' => 'http://hl7.org/fhir/StructureDefinition/MedicationDispense',
                        'interaction' => [
                            ['code' => 'read'],
                            ['code' => 'search-type'],
                        ],
                        'searchParam' => [
                            ['name' => '_id', 'type' => 'token', 'documentation' => 'FHIR MedicationDispense logical id.'],
                            ['name' => 'patient', 'type' => 'reference', 'documentation' => 'Patient reference.'],
                            ['name' => 'subject', 'type' => 'reference', 'documentation' => 'Patient subject reference.'],
                            ['name' => 'encounter', 'type' => 'reference', 'documentation' => 'Encounter reference.'],
                            ['name' => 'prescription', 'type' => 'reference', 'documentation' => 'MedicationRequest reference.'],
                            ['name' => 'status', 'type' => 'token', 'documentation' => 'FHIR MedicationDispense status.'],
                            ['name' => 'whenhandedover', 'type' => 'date', 'documentation' => 'Exact YYYY-MM-DD dispense date.'],
                        ],
                    ],
                ],
            ]],
        ]);
    }

    public function read(string $id, FhirResourceIdentityService $identities): JsonResponse
    {
        $patient = $identities->patientForId($id);
        if (! $patient) {
            return $this->outcome('not-found', 'The requested Patient resource was not found.', 404);
        }

        return $this->fhirJson($this->toFhirPatient($patient, $id));
    }

    public function search(Request $request, FhirResourceIdentityService $identities): JsonResponse
    {
        $allowed = ['_id', 'identifier', 'name', 'family', 'given', 'gender', 'birthdate', '_count', 'page', '_format'];
        $unsupported = array_diff(array_keys($request->query()), $allowed);
        if ($unsupported !== []) {
            return $this->outcome('not-supported', 'Unsupported search parameter: '.implode(', ', $unsupported), 400);
        }

        $validator = Validator::make($request->query(), [
            '_id' => ['sometimes', 'string', 'max:64', 'regex:/^[A-Za-z0-9.-]+$/'],
            'identifier' => ['sometimes', 'string', 'max:255'],
            'name' => ['sometimes', 'string', 'max:100'],
            'family' => ['sometimes', 'string', 'max:100'],
            'given' => ['sometimes', 'string', 'max:100'],
            'gender' => ['sometimes', 'in:male,female,other,unknown'],
            'birthdate' => ['sometimes', 'date_format:Y-m-d'],
            '_count' => ['sometimes', 'integer', 'between:1,100'],
            'page' => ['sometimes', 'integer', 'between:1,100000'],
            '_format' => ['sometimes', 'in:json,application/fhir+json'],
        ]);

        if ($validator->fails()) {
            return $this->outcome('invalid', $validator->errors()->first(), 400);
        }

        $query = Patient::query()->with('demographic');
        $logicalId = $request->query('_id');
        if ($logicalId !== null) {
            $identity = FhirResourceIdentity::query()
                ->where('resource_type', 'Patient')
                ->where('logical_id', $logicalId)
                ->first();
            if (! $identity) {
                return $this->searchBundle(collect(), 0, $request, (int) $request->query('page', 1));
            }
            $query->whereKey($identity->local_key);
        }

        $identifier = $request->query('identifier');
        if ($identifier !== null) {
            [$system, $value] = str_contains($identifier, '|')
                ? explode('|', $identifier, 2)
                : ['', $identifier];
            if ($system !== '' && ! hash_equals((string) config('fhir.patient_identifier_system'), $system)) {
                return $this->searchBundle(collect(), 0, $request, (int) $request->query('page', 1));
            }
            $query->where('hospital_number', $value);
        }

        foreach (['name', 'family', 'given'] as $parameter) {
            $term = trim((string) $request->query($parameter, ''));
            if ($term === '') {
                continue;
            }

            $query->whereHas('demographic', function (Builder $demographic) use ($parameter, $term): void {
                $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
                if ($parameter === 'name') {
                    $demographic->where(function (Builder $names) use ($escaped): void {
                        $names->where('first_name', 'like', $escaped)
                            ->orWhere('last_name', 'like', $escaped);
                    });
                } else {
                    $column = $parameter === 'family' ? 'last_name' : 'first_name';
                    $demographic->where($column, 'like', $escaped);
                }
            });
        }

        $gender = $request->query('gender');
        if ($gender !== null) {
            $localValues = match ($gender) {
                'male' => ['male', 'm'],
                'female' => ['female', 'f'],
                'other' => ['other'],
                default => ['unknown'],
            };
            $query->whereHas('demographic', function (Builder $demographic) use ($localValues, $gender): void {
                if ($gender === 'unknown') {
                    $demographic->where(function (Builder $values): void {
                        $values->whereNull('gender')
                            ->orWhereNotIn(DB::raw('LOWER(gender)'), ['male', 'm', 'female', 'f', 'other']);
                    });

                    return;
                }
                $demographic->whereIn(DB::raw('LOWER(gender)'), $localValues);
            });
        }

        $birthdate = $request->query('birthdate');
        if ($birthdate !== null) {
            $query->whereHas('demographic', fn (Builder $demographic) => $demographic->whereDate('date_of_birth', $birthdate));
        }

        $pageSize = min((int) $request->query('_count', self::PAGE_SIZE), 100);
        $page = (int) $request->query('page', 1);
        if (($page - 1) * $pageSize > 100000) {
            return $this->outcome('too-costly', 'The requested result page is beyond the supported offset range.', 400);
        }

        $patients = $query->orderBy('patients.id')->paginate($pageSize, ['patients.*'], 'page', $page);
        $entries = $patients->getCollection()->map(function (Patient $patient) use ($identities): array {
            $id = $identities->idForPatient($patient);

            return [
                'fullUrl' => rtrim((string) config('fhir.base_url'), '/').'/Patient/'.$id,
                'resource' => $this->toFhirPatient($patient, $id),
                'search' => ['mode' => 'match'],
            ];
        });

        return $this->searchBundle($entries, $patients->total(), $request, $page, $patients->hasMorePages());
    }

    private function toFhirPatient(Patient $patient, string $id): array
    {
        $demographic = $patient->demographic;
        $resource = [
            'resourceType' => 'Patient',
            'id' => $id,
            'active' => true,
            'identifier' => [[
                'use' => 'usual',
                'system' => config('fhir.patient_identifier_system'),
                'value' => (string) $patient->hospital_number,
            ]],
        ];
        if ($patient->updated_at) {
            $resource['meta'] = ['lastUpdated' => $patient->updated_at->toIso8601String()];
        }

        if (! $demographic) {
            return $resource;
        }

        $name = array_filter([
            'use' => 'official',
            'family' => $demographic->last_name,
            'given' => $demographic->first_name ? [$demographic->first_name] : null,
        ], static fn ($value) => $value !== null && $value !== '');
        if (count($name) > 1) {
            $resource['name'] = [$name];
        }

        $gender = strtolower(trim((string) $demographic->gender));
        $resource['gender'] = match ($gender) {
            'male', 'm' => 'male',
            'female', 'f' => 'female',
            'other' => 'other',
            default => 'unknown',
        };

        if ($demographic->date_of_birth) {
            $resource['birthDate'] = $demographic->date_of_birth->toDateString();
        }

        $telecom = array_values(array_filter([
            $demographic->phone_number ? ['system' => 'phone', 'value' => $demographic->phone_number] : null,
            $demographic->email ? ['system' => 'email', 'value' => $demographic->email] : null,
        ]));
        if ($telecom !== []) {
            $resource['telecom'] = $telecom;
        }

        $address = array_filter([
            'text' => $demographic->address,
            'district' => $demographic->lga,
        ], static fn ($value) => $value !== null && $value !== '');
        if ($address !== []) {
            $resource['address'] = [$address];
        }

        return $resource;
    }

    private function searchBundle(iterable $entries, int $total, Request $request, int $page, bool $hasMore = false): JsonResponse
    {
        $base = rtrim((string) config('fhir.base_url'), '/').'/Patient';
        $query = $request->query();
        $query['page'] = $page;
        $links = [[
            'relation' => 'self',
            'url' => $base.'?'.Arr::query($query),
        ]];
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
