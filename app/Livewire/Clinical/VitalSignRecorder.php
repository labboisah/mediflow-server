<?php

namespace App\Livewire\Clinical;

use App\Livewire\Clinical\Concerns\ManagesClinicalVisit;
use App\Models\Patient;
use App\Models\VitalSign;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.modern')]
class VitalSignRecorder extends Component
{
    use ManagesClinicalVisit;

    public array $form = [];
    public ?int $editingId = null;

    public function mount(Patient $patient): void
    {
        $this->patient = $patient;
        $this->form = ['recorded_date' => now()->format('Y-m-d\TH:i')];
    }

    public function render()
    {
        return view('components.clinical.vital-sign-recorder', [
            'recent' => $this->currentVisit()->vitalSigns()->latest('recorded_date')->limit(10)->get(),
        ]);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'form.body_temperature' => ['required', 'numeric'],
            'form.blood_pressure_systolic' => $this->bloodPressureRules('form.blood_pressure_diastolic'),
            'form.blood_pressure_diastolic' => $this->bloodPressureRules('form.blood_pressure_systolic'),
            'form.heart_rate' => ['required', 'numeric'],
            'form.respiratory_rate' => ['required', 'numeric'],
            'form.oxygen_saturation' => ['required', 'numeric'],
            'form.blood_glucose' => ['nullable', 'numeric', 'min:0'],
            'form.weight' => ['nullable', 'numeric', 'min:0'],
            'form.height' => ['nullable', 'numeric', 'min:0'],
            'form.notes' => ['nullable', 'string', 'max:1000'],
            'form.recorded_date' => ['required', 'date'],
        ])['form'];

        if ($this->editingId) {
            $vitalSign = $this->currentVisit()->vitalSigns()->findOrFail($this->editingId);
            $vitalSign->update($validated);
            $this->logActivity('Vital signs updated');
        } else {
            $validated['patient_visit_id'] = $this->currentVisit()->id;
            $validated['recorded_by'] = auth()->id();
            VitalSign::create($validated);
            $this->logActivity('Vital signs recorded');
        }

        $this->editingId = null;
        $this->form = ['recorded_date' => now()->format('Y-m-d\TH:i')];
        $this->feedback('Vital signs saved successfully.');
    }

    public function edit(int $id): void
    {
        $vitalSign = $this->currentVisit()->vitalSigns()->findOrFail($id);
        $this->editingId = $vitalSign->id;
        $this->form = [
            'body_temperature' => $vitalSign->body_temperature,
            'blood_pressure_systolic' => $vitalSign->blood_pressure_systolic,
            'blood_pressure_diastolic' => $vitalSign->blood_pressure_diastolic,
            'heart_rate' => $vitalSign->heart_rate,
            'respiratory_rate' => $vitalSign->respiratory_rate,
            'oxygen_saturation' => $vitalSign->oxygen_saturation,
            'blood_glucose' => $vitalSign->blood_glucose,
            'weight' => $vitalSign->weight,
            'height' => $vitalSign->height,
            'notes' => $vitalSign->notes,
            'recorded_date' => optional($vitalSign->recorded_date)->format('Y-m-d\TH:i'),
        ];
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->form = ['recorded_date' => now()->format('Y-m-d\TH:i')];
        $this->resetValidation();
    }

    private function bloodPressureRules(string $pairedField): array
    {
        $rules = $this->isChildPatient()
            ? ['nullable', 'required_with:' . $pairedField]
            : ['required'];

        return [...$rules, 'numeric', 'between:30,250'];
    }

    private function isChildPatient(): bool
    {
        $dateOfBirth = $this->patient?->demographic?->date_of_birth;

        return $dateOfBirth && $dateOfBirth->age < 18;
    }
}

