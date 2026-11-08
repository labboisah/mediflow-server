<?php

namespace App\Livewire\Admin;

use App\Models\Department;
use App\Models\Revenue;
use App\Models\RevenueCategory;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class RevenueManagement extends Component
{
    use WithPagination;

    public function boot(): void
    {
        app(\App\Services\PackageFinanceAccess::class)->authorize();
    }

    protected string $paginationTheme = 'tailwind';

    public string $search = '';
    public string $category = '';
    public string $department = '';
    public string $createdBy = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sortField = 'revenue_date';
    public string $sortDirection = 'desc';

    public ?int $editingRevenueId = null;
    public string $revenueCategoryId = '';
    public string $departmentId = '';
    public string $title = '';
    public string $amount = '';
    public string $revenueDate = '';
    public string $referenceNumber = '';
    public string $description = '';

    private array $sortableFields = [
        'category' => 'revenue_categories.name',
        'title' => 'revenues.title',
        'amount' => 'revenues.amount',
        'revenue_date' => 'revenues.revenue_date',
        'reference_number' => 'revenues.reference_number',
        'department' => 'departments.name',
        'created_by' => 'users.name',
        'created_at' => 'revenues.created_at',
    ];

    public function mount(): void
    {
        $this->revenueDate = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function updatingDepartment(): void
    {
        $this->resetPage();
    }

    public function updatingCreatedBy(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (!array_key_exists($field, $this->sortableFields)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function edit(int $revenueId): void
    {
        abort_unless($this->canManageFinance(), 403);

        $revenue = Revenue::findOrFail($revenueId);

        $this->editingRevenueId = $revenue->id;
        $this->revenueCategoryId = (string) $revenue->revenue_category_id;
        $this->departmentId = (string) ($revenue->department_id ?? '');
        $this->title = $revenue->title;
        $this->amount = (string) $revenue->amount;
        $this->revenueDate = optional($revenue->revenue_date)->format('Y-m-d') ?? (string) $revenue->revenue_date;
        $this->referenceNumber = $revenue->reference_number ?? '';
        $this->description = $revenue->description ?? '';
    }

    public function save(): void
    {
        abort_unless($this->canManageFinance(), 403);

        $validated = $this->validate([
            'revenueCategoryId' => ['required', 'exists:revenue_categories,id'],
            'departmentId' => ['nullable', 'exists:departments,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'revenueDate' => ['required', 'date'],
            'referenceNumber' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $payload = [
            'revenue_category_id' => $validated['revenueCategoryId'],
            'department_id' => $validated['departmentId'] ?: null,
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'revenue_date' => $validated['revenueDate'],
            'reference_number' => $validated['referenceNumber'],
            'description' => $validated['description'],
        ];

        if ($this->editingRevenueId) {
            Revenue::findOrFail($this->editingRevenueId)->update($payload);
            $message = 'Revenue updated successfully.';
        } else {
            Revenue::create(array_merge($payload, [
                'created_by' => auth()->id(),
            ]));
            $message = 'Revenue recorded successfully.';
        }

        $this->resetForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function delete(int $revenueId): void
    {
        abort_unless($this->canManageFinance(), 403);

        Revenue::findOrFail($revenueId)->delete();

        if ($this->editingRevenueId === $revenueId) {
            $this->resetForm();
        }

        $this->dispatch('toast', message: 'Revenue deleted successfully.', type: 'danger');
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'category', 'department', 'createdBy', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function resetForm(): void
    {
        $this->reset([
            'editingRevenueId',
            'revenueCategoryId',
            'departmentId',
            'title',
            'amount',
            'revenueDate',
            'referenceNumber',
            'description',
        ]);

        $this->revenueDate = now()->format('Y-m-d');
        $this->resetValidation();
    }

    public function render()
    {
        $query = Revenue::query()
            ->select('revenues.*')
            ->with(['category', 'department', 'createdBy'])
            ->leftJoin('revenue_categories', 'revenues.revenue_category_id', '=', 'revenue_categories.id')
            ->leftJoin('departments', 'revenues.department_id', '=', 'departments.id')
            ->leftJoin('users', 'revenues.created_by', '=', 'users.id');

        if ($this->search !== '') {
            $search = $this->search;

            $query->where(function ($q) use ($search) {
                $q->where('revenues.title', 'like', "%{$search}%")
                    ->orWhere('revenues.reference_number', 'like', "%{$search}%")
                    ->orWhere('revenues.description', 'like', "%{$search}%")
                    ->orWhere('revenue_categories.name', 'like', "%{$search}%")
                    ->orWhere('departments.name', 'like', "%{$search}%")
                    ->orWhere('users.name', 'like', "%{$search}%");
            });
        }

        if ($this->category !== '') {
            $query->where('revenues.revenue_category_id', $this->category);
        }

        if ($this->department !== '') {
            $this->department === 'general'
                ? $query->whereNull('revenues.department_id')
                : $query->where('revenues.department_id', $this->department);
        }

        if ($this->createdBy !== '') {
            $query->where('revenues.created_by', $this->createdBy);
        }

        if ($this->dateFrom !== '') {
            $query->whereDate('revenues.revenue_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('revenues.revenue_date', '<=', $this->dateTo);
        }

        $summaryQuery = clone $query;
        $sortColumn = $this->sortableFields[$this->sortField] ?? 'revenues.revenue_date';

        return view('components.admin.revenue-management', [
            'revenues' => $query
                ->orderBy($sortColumn, $this->sortDirection)
                ->paginate(15),
            'categories' => RevenueCategory::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'users' => User::query()
                ->whereIn('id', Revenue::query()->select('created_by')->whereNotNull('created_by'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'totalRevenue' => (clone $summaryQuery)->sum('revenues.amount'),
            'recordCount' => (clone $summaryQuery)->count(),
            'averageRevenue' => (clone $summaryQuery)->avg('revenues.amount') ?? 0,
            'categoryCount' => (clone $summaryQuery)->distinct('revenues.revenue_category_id')->count('revenues.revenue_category_id'),
            'canManageFinance' => $this->canManageFinance(),
            'pdfUrl' => route((request()->routeIs('finance.*') ? 'finance' : 'admin') . '.revenues.pdf', array_filter([
                'search' => $this->search ?: null,
                'category' => $this->category ?: null,
                'department' => $this->department ?: null,
                'created_by' => $this->createdBy ?: null,
                'date_from' => $this->dateFrom ?: null,
                'date_to' => $this->dateTo ?: null,
            ])),
        ]);
    }

    private function canManageFinance(): bool
    {
        return auth()->user()?->hasAnyRole(['administrator', 'finance_officer']) ?? false;
    }

    public function sortIcon(string $field): string
    {
        if ($this->sortField !== $field) {
            return 'bi-arrow-down-up';
        }

        return $this->sortDirection === 'asc' ? 'bi-sort-up' : 'bi-sort-down';
    }
}

