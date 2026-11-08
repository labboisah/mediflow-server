<?php

namespace App\Livewire\Midwife;

use App\Models\Patient;

class NewbornManagement extends MaternityVisitWorkspace
{
    public function mount(?Patient $patient = null, bool $compact = false): void
    {
        $this->activity = 'newborn';
        $this->fixedActivity = 'newborn';
        $this->pageTitle = 'Newborn Management';
        $this->pageDescription = 'Record newborn care directly against the selected patient visit.';

        parent::mount($patient, $compact);
    }
}
