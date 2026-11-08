<?php

namespace App\Livewire\Clinical;

use App\Models\Admission;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.modern')]
class DischargeWorkspace extends Component
{
    public Admission $admission;
    public string $reason = '';
    public string $date = '';
    public string $time = '';
    public string $nextAppointmentDate = '';
    public ?string $feedbackMessage = null;
    public string $feedbackType = 'success';

    public function mount(Admission $admission): void
    {
        $this->admission = $admission->load(['patientVisit.patient', 'discharge']);
        $discharge = $this->admission->discharge;

        $this->reason = (string) ($discharge?->reason ?? '');
        $this->date = $discharge ? Carbon::parse($discharge->date)->toDateString() : now()->toDateString();
        $this->time = (string) ($discharge?->time ?? now()->format('H:i'));
        $this->nextAppointmentDate = $discharge?->next_appointment_date
            ? Carbon::parse($discharge->next_appointment_date)->toDateString()
            : '';
    }

    public function render()
    {
        return view('components.clinical.discharge-workspace');
    }

    public function save(): void
    {
        $validated = $this->validate([
            'reason' => ['required', 'string', 'max:10000'],
            'date' => ['required', 'date'],
            'time' => ['required'],
            'nextAppointmentDate' => ['nullable', 'date'],
        ]);

        $payload = [
            'reason' => $validated['reason'],
            'date' => $validated['date'],
            'time' => $validated['time'],
            'next_appointment_date' => $validated['nextAppointmentDate'] ?: null,
        ];

        if ($this->admission->discharge) {
            $this->admission->discharge->update($payload);
            $activity = "Discharge summary updated with reason: {$validated['reason']}";
        } else {
            $this->admission->discharge()->create($payload + ['discharge_by' => auth()->id()]);
            $activity = "Patient discharged with reason: {$validated['reason']}";
        }

        $this->admission->update(['status' => 'discharged']);
        $this->admission->patientVisit->update(['status' => 'discharged']);
        $this->admission->loadMissing('bed');
        $this->admission->releaseBedIfNoActiveAdmission();
        $this->admission->patientVisit->visitActivities()->create([
            'activity' => $activity,
            'recorded_by' => auth()->id(),
        ]);

        $this->admission->load('discharge');
        $this->feedback('Discharge saved successfully.');
    }

    private function feedback(string $message, string $type = 'success'): void
    {
        $this->feedbackMessage = $message;
        $this->feedbackType = $type;
    }
}

