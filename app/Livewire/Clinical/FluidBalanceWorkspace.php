<?php

namespace App\Livewire\Clinical;

use App\Livewire\Clinical\Concerns\ManagesClinicalVisit;
use App\Models\Patient;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.modern')]
class FluidBalanceWorkspace extends Component
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
        $admission = $this->currentVisit()->confirmAdmission();

        return view('components.clinical.fluid-balance-workspace', [
            'admission' => $admission,
            'recent' => $admission ? $admission->fluidBalances()->latest()->limit(10)->get() : collect(),
        ]);
    }

    public function save(): void
    {
        $admission = $this->currentVisit()->confirmAdmission();

        if (! $admission) {
            $this->feedback('A confirmed admission is required before recording fluid balance.', 'warning');
            return;
        }

        $validated = $this->validate([
            'form.date' => ['required', 'date'],
            'form.time' => ['required'],
            'form.type_in' => ['nullable', 'string', 'max:255'],
            'form.tube_in' => ['nullable', 'string', 'max:255'],
            'form.oral' => ['nullable', 'numeric', 'min:0'],
            'form.iv' => ['nullable', 'numeric', 'min:0'],
            'form.type_out' => ['nullable', 'string', 'max:255'],
            'form.tube_out' => ['nullable', 'string', 'max:255'],
            'form.urine' => ['nullable', 'numeric', 'min:0'],
            'form.faces' => ['nullable', 'numeric', 'min:0'],
        ])['form'];

        if ($this->editingId) {
            $admission->fluidBalances()->findOrFail($this->editingId)->update($validated);
            $this->logActivity('Fluid balance entry updated');
        } else {
            $admission->fluidBalances()->create($validated + ['recorded_by' => auth()->id()]);
            $this->logActivity('Fluid balance entry recorded');
        }

        $this->editingId = null;
        $this->form = ['date' => now()->toDateString(), 'time' => now()->format('H:i')];
        $this->feedback('Fluid balance saved successfully.');
    }

    public function edit(int $id): void
    {
        $admission = $this->currentVisit()->confirmAdmission();
        $fluid = $admission?->fluidBalances()->findOrFail($id);

        if (! $fluid) {
            return;
        }

        $this->editingId = $fluid->id;
        $this->form = [
            'date' => $fluid->date,
            'time' => $fluid->time,
            'type_in' => $fluid->type_in,
            'tube_in' => $fluid->tube_in,
            'oral' => $fluid->oral,
            'iv' => $fluid->iv,
            'type_out' => $fluid->type_out,
            'tube_out' => $fluid->tube_out,
            'urine' => $fluid->urine,
            'faces' => $fluid->faces,
        ];
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->form = ['date' => now()->toDateString(), 'time' => now()->format('H:i')];
        $this->resetValidation();
    }
}

