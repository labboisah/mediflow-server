<?php

namespace App\Livewire\Midwife;

use App\Models\Patient;

class LabourManagement extends MaternityVisitWorkspace
{
    public function mount(?Patient $patient = null, bool $compact = false): void
    {
        $this->activity = 'labour';
        $this->fixedActivity = 'labour';
        $this->pageTitle = 'Labour Management';
        $this->pageDescription = 'Record labour care directly against the selected patient visit.';

        parent::mount($patient, $compact);
    }
}
