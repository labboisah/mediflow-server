<?php

namespace App\Livewire\Department;

use App\Models\Consumable;
use App\Models\ConsumableUsage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class ConsumableUsageManagement extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $consumableId = '';
    public string $assignedTo = '';
    public string $quantity = '';
    public string $usageDate = '';
    public string $purpose = '';
    public string $notes = '';

    public string $dateFrom = '';
    public string $dateTo = '';
    public string $userFilter = '';
    public string $consumableFilter = '';

    public function mount(): void
    {
        $this->usageDate = today()->toDateString();
        $this->dateFrom = today()->startOfMonth()->toDateString();
        $this->dateTo = today()->toDateString();
    }

    public function render()
    {
        $usageQuery = $this->usageQuery();

        return view('components.department.consumable-usage-management', [
            'consumables' => $this->departmentConsumables()->orderBy('name')->get(),
            'users' => $this->departmentUsers()->orderBy('name')->get(),
            'usages' => (clone $usageQuery)->latest('usage_date')->latest()->paginate(15),
            'reportRows' => $this->reportRows(),
            'summary' => [
                'quantity' => (clone $usageQuery)->sum('quantity'),
                'records' => (clone $usageQuery)->count(),
                'lowStock' => $this->departmentConsumables()->whereColumn('current_quantity', '<=', 'reorder_level')->count(),
            ],
        ]);
    }

    public function assign(): void
    {
        $validated = $this->validate([
            'consumableId' => ['required', 'exists:consumables,id'],
            'assignedTo' => ['required', 'exists:users,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'usageDate' => ['required', 'date'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $departmentId = auth()->user()->department_id;
        $user = $this->departmentUsers()->findOrFail($validated['assignedTo']);
        $quantity = (float) $validated['quantity'];

        $assigned = false;
        $availableStock = 0;

        DB::transaction(function () use ($departmentId, $user, $quantity, $validated, &$assigned, &$availableStock) {
            $consumable = $this->departmentConsumables()->lockForUpdate()->findOrFail($validated['consumableId']);
            $availableStock = (float) $consumable->current_quantity;

            if ($availableStock < $quantity) {
                return;
            }

            ConsumableUsage::create([
                'department_id' => $departmentId,
                'consumable_id' => $consumable->id,
                'assigned_to' => $user->id,
                'assigned_by' => auth()->id(),
                'quantity' => $quantity,
                'usage_date' => $validated['usageDate'],
                'purpose' => $validated['purpose'],
                'notes' => $validated['notes'],
            ]);

            $consumable->decrement('current_quantity', $quantity);
            $assigned = true;
        });

        if (! $assigned) {
            $this->addError('quantity', 'Requested quantity is greater than available stock of ' . number_format($availableStock, 2) . '.');
            return;
        }

        $this->resetAssignment();
        $this->dispatch('toast', message: 'Consumable assigned and stock count reduced.', type: 'success');
    }

    public function resetAssignment(): void
    {
        $this->reset(['consumableId', 'assignedTo', 'quantity', 'purpose', 'notes']);
        $this->usageDate = today()->toDateString();
        $this->resetValidation();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedUserFilter(): void
    {
        $this->resetPage();
    }

    public function updatedConsumableFilter(): void
    {
        $this->resetPage();
    }

    private function usageQuery()
    {
        return ConsumableUsage::query()
            ->with(['consumable', 'assignedTo', 'assignedBy'])
            ->where('department_id', auth()->user()->department_id)
            ->when($this->dateFrom !== '', fn ($query) => $query->whereDate('usage_date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($query) => $query->whereDate('usage_date', '<=', $this->dateTo))
            ->when($this->userFilter !== '', fn ($query) => $query->where('assigned_to', $this->userFilter))
            ->when($this->consumableFilter !== '', fn ($query) => $query->where('consumable_id', $this->consumableFilter));
    }

    private function reportRows()
    {
        return (clone $this->usageQuery())
            ->select('consumable_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('COUNT(*) as usage_count'))
            ->with('consumable')
            ->groupBy('consumable_id')
            ->orderByDesc('total_quantity')
            ->get();
    }

    private function departmentConsumables()
    {
        return Consumable::query()->where('department_id', auth()->user()->department_id);
    }

    private function departmentUsers()
    {
        return User::query()->where('department_id', auth()->user()->department_id);
    }
}

