<?php

namespace App\Livewire\Midwife;

use App\Models\Patient;

class PostnatalManagement extends MaternityVisitWorkspace
{
    public function mount(?Patient $patient = null, bool $compact = false): void
    {
        $this->activity = 'postnatal';
        $this->fixedActivity = 'postnatal';
        $this->pageTitle = 'Postnatal Management';
        $this->pageDescription = 'Record postnatal examination directly against the selected patient visit.';

        parent::mount($patient, $compact);
    }
}
