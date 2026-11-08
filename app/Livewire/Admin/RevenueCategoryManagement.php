<?php

namespace App\Livewire\Admin;

use App\Models\RevenueCategory;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class RevenueCategoryManagement extends Component
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
    public string $description = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $categoryId): void
    {
        abort_unless($this->isAdministrator(), 403);

        $category = RevenueCategory::findOrFail($categoryId);

        $this->editingCategoryId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
    }

    public function save(): void
    {
        abort_unless($this->isAdministrator(), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->editingCategoryId) {
            RevenueCategory::findOrFail($this->editingCategoryId)->update($validated);
            $message = 'Revenue category updated successfully.';
        } else {
            RevenueCategory::create($validated);
            $message = 'Revenue category created successfully.';
        }

        $this->resetForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function delete(int $categoryId): void
    {
        abort_unless($this->isAdministrator(), 403);

        $category = RevenueCategory::withCount('revenues')->findOrFail($categoryId);

        if ($category->revenues_count > 0) {
            $this->dispatch('toast', message: 'Cannot delete category: it has ' . $category->revenues_count . ' linked revenue(s).', type: 'danger');
            return;
        }

        $category->delete();

        if ($this->editingCategoryId === $categoryId) {
            $this->resetForm();
        }

        $this->dispatch('toast', message: 'Revenue category deleted successfully.', type: 'danger');
    }

    public function resetForm(): void
    {
        $this->reset(['editingCategoryId', 'name', 'description']);
        $this->resetValidation();
    }

    public function render()
    {
        $query = RevenueCategory::withCount('revenues')
            ->orderBy('name');

        if ($this->search !== '') {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return view('components.admin.revenue-category-management', [
            'revenueCategories' => $query->paginate(20),
        ]);
    }

    private function isAdministrator(): bool
    {
        return auth()->user()?->hasRole('administrator') ?? false;
    }
}

