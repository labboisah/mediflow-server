<?php

namespace App\Livewire\Clinical;

use App\Livewire\Clinical\Concerns\ManagesClinicalVisit;
use App\Models\Patient;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.modern')]
class ObservationRecorder extends Component
{
    use ManagesClinicalVisit;

    public array $form = [];
    public ?int $editingId = null;

    public function mount(Patient $patient): void
    {
        $this->patient = $patient;
        $this->form = ['date' => now()->toDateString(), 'time' => now()->format('H:i')];
    }

    public function render()
    {
        return view('components.clinical.observation-recorder', [
            'recent' => $this->currentVisit()->observations()->latest()->limit(10)->get(),
        ]);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'form.temperature' => ['nullable', 'numeric'],
            'form.mate_pulse' => ['nullable', 'numeric'],
            'form.blood_pressure_systolic' => ['nullable', 'integer'],
            'form.blood_pressure_diastolic' => ['nullable', 'integer'],
            'form.respiratory_rate' => ['nullable', 'integer'],
            'form.drop_rate' => ['nullable', 'integer'],
            'form.constraction' => ['nullable', 'string', 'max:255'],
            'form.fits' => ['nullable', 'string', 'max:255'],
            'form.date' => ['required', 'date'],
            'form.time' => ['required'],
            'form.remark' => ['nullable', 'string', 'max:10000'],
        ])['form'];

        $payload = [
            'temperature' => $validated['temperature'] ?? null,
            'mate_pulse' => $validated['mate_pulse'] ?? null,
            'blood_pressure' => ! empty($validated['blood_pressure_systolic']) && ! empty($validated['blood_pressure_diastolic'])
                ? $validated['blood_pressure_systolic'] . '/' . $validated['blood_pressure_diastolic']
                : null,
            'respiration' => $validated['respiratory_rate'] ?? null,
            'drop_rate' => $validated['drop_rate'] ?? null,
            'constraction' => $validated['constraction'] ?? null,
            'fits' => $validated['fits'] ?? null,
            'date' => $validated['date'],
            'time' => $validated['time'],
            'remark' => $validated['remark'] ?? null,
        ];

        if ($this->editingId) {
            $this->currentVisit()->observations()->findOrFail($this->editingId)->update($payload);
            $this->logActivity('Observation updated');
        } else {
            $this->currentVisit()->observations()->create($payload + ['recorded_by' => auth()->id()]);
            $this->logActivity('Observation recorded');
        }

        $this->editingId = null;
        $this->form = ['date' => now()->toDateString(), 'time' => now()->format('H:i')];
        $this->feedback('Observation saved successfully.');
    }

    public function edit(int $id): void
    {
        $observation = $this->currentVisit()->observations()->findOrFail($id);
        [$systolic, $diastolic] = array_pad(explode('/', (string) $observation->blood_pressure, 2), 2, null);

        $this->editingId = $observation->id;
        $this->form = [
            'temperature' => $observation->temperature,
            'mate_pulse' => $observation->mate_pulse,
            'blood_pressure_systolic' => $systolic,
            'blood_pressure_diastolic' => $diastolic,
            'respiratory_rate' => $observation->respiration,
            'drop_rate' => $observation->drop_rate,
            'constraction' => $observation->constraction,
            'fits' => $observation->fits,
            'date' => $observation->date,
            'time' => $observation->time,
            'remark' => $observation->remark,
        ];
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->form = ['date' => now()->toDateString(), 'time' => now()->format('H:i')];
        $this->resetValidation();
    }
}

