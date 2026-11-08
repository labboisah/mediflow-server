<?php

namespace App\Livewire\Department;

use App\Models\Consumable;
use App\Models\ConsumableStock;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class ConsumableStockManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';
    public ?int $editingId = null;
    public string $consumableId = '';
    public string $quantity = '';
    public string $unitPrice = '';
    public string $purchaseDate = '';
    public string $reference = '';

    public function mount(): void
    {
        $this->purchaseDate = today()->toDateString();
    }

    public function render()
    {
        return view('components.department.consumable-stock-management', [
            'consumables' => $this->departmentConsumables()->orderBy('name')->get(),
            'stocks' => ConsumableStock::query()
                ->with('consumable')
                ->whereHas('consumable', fn ($query) => $query->where('department_id', auth()->user()->department_id))
                ->when(trim($this->search) !== '', function ($query) {
                    $search = trim($this->search);
                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('reference', 'like', "%{$search}%")
                            ->orWhereHas('consumable', fn ($item) => $item->where('name', 'like', "%{$search}%"));
                    });
                })
                ->latest()
                ->paginate(15),
        ]);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'consumableId' => ['required', 'exists:consumables,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unitPrice' => ['required', 'numeric', 'min:0'],
            'purchaseDate' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $consumable = $this->departmentConsumables()->findOrFail($validated['consumableId']);

        $payload = [
            'consumable_id' => $consumable->id,
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unitPrice'],
            'purchase_date' => $validated['purchaseDate'],
            'reference' => $validated['reference'],
        ];

        if ($this->editingId) {
            ConsumableStock::whereKey($this->editingId)
                ->whereHas('consumable', fn ($query) => $query->where('department_id', auth()->user()->department_id))
                ->firstOrFail()
                ->update($payload);
            $message = 'Consumable stock updated successfully.';
        } else {
            ConsumableStock::create($payload);
            $message = 'Consumable stock added successfully.';
        }

        $this->resetForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function edit(int $id): void
    {
        $stock = ConsumableStock::whereKey($id)
            ->whereHas('consumable', fn ($query) => $query->where('department_id', auth()->user()->department_id))
            ->firstOrFail();

        $this->editingId = $stock->id;
        $this->consumableId = (string) $stock->consumable_id;
        $this->quantity = (string) $stock->quantity;
        $this->unitPrice = (string) $stock->unit_price;
        $this->purchaseDate = $stock->purchase_date?->toDateString() ?? today()->toDateString();
        $this->reference = $stock->reference ?? '';
    }

    public function delete(int $id): void
    {
        ConsumableStock::whereKey($id)
            ->whereHas('consumable', fn ($query) => $query->where('department_id', auth()->user()->department_id))
            ->firstOrFail()
            ->delete();

        $this->dispatch('toast', message: 'Consumable stock deleted successfully.', type: 'danger');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'consumableId', 'quantity', 'unitPrice', 'reference']);
        $this->purchaseDate = today()->toDateString();
        $this->resetValidation();
    }

    private function departmentConsumables()
    {
        return Consumable::query()->where('department_id', auth()->user()->department_id);
    }
}

