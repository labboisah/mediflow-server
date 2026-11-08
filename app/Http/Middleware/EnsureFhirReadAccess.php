<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Models\DiagnosticReport;
use App\Models\InvestigationRequest;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\PharmacyDispense;
use App\Models\PrescriptionItem;
use App\Models\VitalSign;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureFhirReadAccess
{
    private const RESOURCE_RULES = [
        'Patient' => ['ability' => 'fhir.patient.read', 'permission' => 'patient.read', 'model' => Patient::class],
        'Encounter' => ['ability' => 'fhir.encounter.read', 'permission' => 'visit.read', 'model' => PatientVisit::class],
        'Observation' => ['ability' => 'fhir.observation.read', 'permission' => 'vital_sign.read', 'model' => VitalSign::class],
        'ServiceRequest' => ['ability' => 'fhir.servicerequest.read', 'permissions' => ['investigation_request.read', 'service_request.read'], 'model' => InvestigationRequest::class],
        'DiagnosticReport' => ['ability' => 'fhir.diagnosticreport.read', 'permission' => 'investigation_result.read', 'model' => DiagnosticReport::class],
        'Medication' => ['ability' => 'fhir.medication.read', 'permission' => 'medicine.read', 'model' => Medicine::class],
        'MedicationRequest' => ['ability' => 'fhir.medicationrequest.read', 'permission' => 'prescription.read', 'model' => PrescriptionItem::class],
        'MedicationDispense' => ['ability' => 'fhir.medicationdispense.read', 'permission' => 'dispense.read', 'model' => PharmacyDispense::class],
    ];

    public function handle(Request $request, Closure $next, string $resourceType): Response
    {
        if (! config('fhir.enabled')) {
            return $this->outcome('not-found', 'FHIR API is not enabled.', 404);
        }

        $rule = self::RESOURCE_RULES[$resourceType] ?? null;
        if (! $rule) {
            return $this->outcome('not-supported', 'FHIR resource type is not enabled.', 404);
        }

        $user = $request->user('sanctum');
        $token = $user?->currentAccessToken();

        if (! $user || ! $user->active || ! $token instanceof PersonalAccessToken) {
            return $this->outcome('login', 'A valid FHIR bearer token is required.', 401, [
                'WWW-Authenticate' => 'Bearer',
            ]);
        }

        $hasPermission = isset($rule['permissions'])
            ? $user->hasPermission($rule['permissions'])
            : $user->hasPermission($rule['permission']);
        if (! $token->can($rule['ability']) || ! $hasPermission) {
            return $this->outcome('forbidden', 'The token and user must both have read access to this resource.', 403);
        }

        $response = $next($request);
        $auditMeta = ['status' => $response->getStatusCode()];
        if ($request->route('id') !== null) {
            $auditMeta['resource_id'] = (string) $request->route('id');
        } elseif ($response->isSuccessful()) {
            $bundle = json_decode($response->getContent(), true);
            $auditMeta['result_count'] = is_array($bundle) ? ($bundle['total'] ?? null) : null;
        }

        AuditLog::record(
            $user,
            'fhir.'.strtolower($resourceType).'.'.($request->routeIs('fhir.'.strtolower($resourceType).'.search') ? 'search' : 'read'),
            $rule['model'],
            meta: $auditMeta,
        );

        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    private function outcome(string $code, string $diagnostics, int $status, array $headers = []): Response
    {
        return response()->json([
            'resourceType' => 'OperationOutcome',
            'issue' => [[
                'severity' => 'error',
                'code' => $code,
                'diagnostics' => $diagnostics,
            ]],
        ], $status, array_merge([
            'Content-Type' => 'application/fhir+json; charset=utf-8',
            'Cache-Control' => 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ], $headers));
    }
}
