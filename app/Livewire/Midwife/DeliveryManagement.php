<?php

namespace App\Livewire\Midwife;

use App\Models\Patient;

class DeliveryManagement extends MaternityVisitWorkspace
{
    public function mount(?Patient $patient = null, bool $compact = false): void
    {
        $this->activity = 'delivery';
        $this->fixedActivity = 'delivery';
        $this->pageTitle = 'Delivery Management';
        $this->pageDescription = 'Record delivery care directly against the selected patient visit.';

        parent::mount($patient, $compact);
    }
}
