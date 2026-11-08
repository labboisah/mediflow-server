<?php

namespace App\Livewire\Clinical\Concerns;

use App\Models\Patient;
use App\Models\PatientVisit;

trait ManagesClinicalVisit
{
    public Patient $patient;
    public ?string $feedbackMessage = null;
    public string $feedbackType = 'success';

    protected function currentVisit(): PatientVisit
    {
        $visit = $this->patient->currentVisit();

        if ($visit) {
            return $visit;
        }

        return $this->patient->patientVisits()->create([
            'visit_date' => now(),
            'visit_type' => 'Clinical',
            'reason_for_visit' => 'Clinical activity',
            'created_by' => auth()->id(),
        ]);
    }

    protected function logActivity(string $activity): void
    {
        $this->currentVisit()->visitActivities()->create([
            'activity' => $activity,
            'recorded_by' => auth()->id(),
        ]);
    }

    protected function feedback(string $message, string $type = 'success'): void
    {
        $this->feedbackMessage = $message;
        $this->feedbackType = $type;
    }
}
