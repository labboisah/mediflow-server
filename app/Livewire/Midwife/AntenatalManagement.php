<?php

namespace App\Livewire\Midwife;

use App\Models\Patient;

class AntenatalManagement extends MaternityVisitWorkspace
{
    public function mount(?Patient $patient = null, bool $compact = false): void
    {
        $this->activity = 'antenatal';
        $this->fixedActivity = 'antenatal';
        $this->pageTitle = 'Antenatal Care Management';
        $this->pageDescription = 'Record antenatal care directly against the selected patient visit.';

        parent::mount($patient, $compact);
    }
}
