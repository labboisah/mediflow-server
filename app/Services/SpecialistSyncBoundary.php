<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\InvestigationRequest;
use App\Models\PatientVisit;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;

// Specialist's extension graph and private documents are local to this installation.
// Exporting only its shared roots would lose assignments, signed notes and provenance.
class SpecialistSyncBoundary
{
    public function localOnly(Model $model): bool
    {
        if ($model->getAttribute('specialist_consultation_id') || $model->getAttribute('specialist_origin')) {
            return true;
        }
        if ($model instanceof Payment && $model->bill_id) {
            return (bool) Bill::withTrashed()->find($model->bill_id)?->specialist_consultation_id;
        }
        if ($model->getAttribute('investigation_request_id')) {
            if (InvestigationRequest::withTrashed()->find($model->investigation_request_id)?->specialist_consultation_id) {
                return true;
            }
        }
        if ($model->getAttribute('patient_visit_id')) {
            return (bool) PatientVisit::withTrashed()->find($model->patient_visit_id)?->specialist_origin;
        }

        return false;
    }
}
