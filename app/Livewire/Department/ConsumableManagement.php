<?php

namespace App\Livewire\Department;

use App\Models\Consumable;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class ConsumableManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';
    public ?int $editingId = null;
    public string $name = '';
    public string $unit = '';
    public string $reorderLevel = '0';

    public function render()
    {
        return view('components.department.consumable-management', [
            'consumables' => $this->departmentConsumablesQuery()
                ->when(trim($this->search) !== '', fn ($query) => $query->where('name', 'like', '%' . trim($this->search) . '%'))
                ->latest()
                ->paginate(15),
            'lowStockCount' => (clone $this->departmentConsumablesQuery())
                ->whereColumn('current_quantity', '<=', 'reorder_level')
                ->count(),
        ]);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'reorderLevel' => ['required', 'numeric', 'min:0'],
        ]);

        $payload = [
            'department_id' => auth()->user()->department_id,
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'reorder_level' => $validated['reorderLevel'],
        ];

        if ($this->editingId) {
            $this->departmentConsumablesQuery()->findOrFail($this->editingId)->update($payload);
            $message = 'Consumable updated successfully.';
        } else {
            Consumable::create($payload);
            $message = 'Consumable created successfully.';
        }

        $this->resetForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function edit(int $id): void
    {
        $consumable = $this->departmentConsumablesQuery()->findOrFail($id);

        $this->editingId = $consumable->id;
        $this->name = $consumable->name;
        $this->unit = $consumable->unit;
        $this->reorderLevel = (string) $consumable->reorder_level;
    }

    public function delete(int $id): void
    {
        $this->departmentConsumablesQuery()->findOrFail($id)->delete();
        $this->dispatch('toast', message: 'Consumable deleted successfully.', type: 'danger');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'unit']);
        $this->reorderLevel = '0';
        $this->resetValidation();
    }

    private function departmentConsumablesQuery()
    {
        return Consumable::query()->where('department_id', auth()->user()->department_id);
    }
}

