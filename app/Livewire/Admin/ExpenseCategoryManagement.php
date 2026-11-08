<?php

namespace App\Livewire\Admin;

use App\Models\ExpenseCategory;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class ExpenseCategoryManagement extends Component
{
    use WithPagination;

    public function boot(): void
    {
        app(\App\Services\PackageFinanceAccess::class)->authorize();
    }

    protected string $paginationTheme = 'tailwind';

    public string $search = '';
    public ?int $editingCategoryId = null;
    public string $name = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $categoryId): void
    {
        abort_unless($this->isAdministrator(), 403);

        $category = ExpenseCategory::findOrFail($categoryId);

        $this->editingCategoryId = $category->id;
        $this->name = $category->name;
    }

    public function save(): void
    {
        abort_unless($this->isAdministrator(), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($this->editingCategoryId) {
            ExpenseCategory::findOrFail($this->editingCategoryId)->update($validated);
            $message = 'Expense category updated successfully.';
        } else {
            ExpenseCategory::create($validated);
            $message = 'Expense category created successfully.';
        }

        $this->resetForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function delete(int $categoryId): void
    {
        abort_unless($this->isAdministrator(), 403);

        $category = ExpenseCategory::withCount('expenses')->findOrFail($categoryId);

        if ($category->expenses_count > 0) {
            $this->dispatch('toast', message: 'Cannot delete category: it has ' . $category->expenses_count . ' linked expense(s).', type: 'danger');
            return;
        }

        $category->delete();

        if ($this->editingCategoryId === $categoryId) {
            $this->resetForm();
        }

        $this->dispatch('toast', message: 'Expense category deleted successfully.', type: 'danger');
    }

    public function resetForm(): void
    {
        $this->reset(['editingCategoryId', 'name']);
        $this->resetValidation();
    }

    public function render()
    {
        $query = ExpenseCategory::withCount('expenses')
            ->orderBy('name');

        if ($this->search !== '') {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return view('components.admin.expense-category-management', [
            'expenseCategories' => $query->paginate(20),
        ]);
    }

    private function isAdministrator(): bool
    {
        return auth()->user()?->hasRole('administrator') ?? false;
    }
}

