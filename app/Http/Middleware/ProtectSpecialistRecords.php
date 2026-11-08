<?php

namespace App\Http\Middleware;

use App\Models\InvestigationRequest;
use App\Models\Prescription;
use Closure;
use Illuminate\Http\Request;

class ProtectSpecialistRecords
{
    public function handle(Request $request, Closure $next)
    {
        $name = $request->route()?->getName() ?? '';
        $user = $request->user();
        if ($user && $user->hasAnyRole(['specialist', 'specialist_assistant', 'specialist_manager', 'specialist_cashier', 'specialist_receiver'])
            && ! $user->hasAnyRole(['administrator', 'doctor', 'nurse', 'record_officer', 'pharmacist', 'lab_technician', 'radiologist', 'radiographer', 'midwife'])) {
            // Specialist staff use scoped commands, not legacy shared endpoints guarded only by authentication.
            abort_if(str_starts_with($name, 'patient.') || str_starts_with($name, 'vital_signs.'), 403);
        }
        foreach ($request->route()?->parameters() ?? [] as $record) {
            if ($record instanceof InvestigationRequest && $record->is_external) {
                abort_if(str_starts_with($name, 'lab.') || str_starts_with($name, 'radiology.') || str_starts_with($name, 'patient.investigation.'), 404);
            }
            if ($record instanceof Prescription && $record->fulfillment === 'external') {
                abort_if(str_starts_with($name, 'pharmacy.'), 404);
            }
        }
        $response = $next($request);
        if (str_starts_with($name, 'specialist.') || $name === 'clinical-history.specialist') {
            $response->headers->set('Cache-Control', 'private, no-store');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        return $response;
    }
}
