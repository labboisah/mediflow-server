<?php

namespace App\Livewire\Midwife;

use App\Models\Patient;

class ChildFollowUpManagement extends MaternityVisitWorkspace
{
    public function mount(?Patient $patient = null, bool $compact = false): void
    {
        $this->activity = 'child_follow_up';
        $this->fixedActivity = 'child_follow_up';
        $this->pageTitle = 'Child Follow-up Management';
        $this->pageDescription = 'Record child follow-up directly against the selected patient visit.';

        parent::mount($patient, $compact);
    }
}
