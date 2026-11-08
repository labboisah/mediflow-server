<?php

namespace App\Livewire\Admin;

use App\Models\FileType;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.modern')]
class FileTypeManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = 'active';
    public string $name = '';
    public string $price = '';
    public ?int $editingFileTypeId = null;

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $duplicate = FileType::withTrashed()
            ->where('name', $validated['name'])
            ->when($this->editingFileTypeId, fn ($query) => $query->whereKeyNot($this->editingFileTypeId))
            ->first();

        if ($duplicate) {
            $this->addError('name', $duplicate->trashed()
                ? 'This file type exists in deleted file types. Restore it instead of creating a duplicate.'
                : 'This file type already exists.');
            return;
        }

        $payload = [
            'name' => trim($validated['name']),
            'price' => $validated['price'] !== '' ? (float) $validated['price'] : null,
        ];

        if ($this->editingFileTypeId) {
            FileType::findOrFail($this->editingFileTypeId)->update($payload);
            $message = 'File type updated successfully.';
        } else {
            FileType::create($payload);
            $message = 'File type created successfully.';
        }

        $this->resetForm();
        $this->dispatch('toast', message: $message, type: 'success');
    }

    public function edit(int $fileTypeId): void
    {
        $fileType = FileType::findOrFail($fileTypeId);

        $this->editingFileTypeId = $fileType->id;
        $this->name = $fileType->name;
        $this->price = (string) $fileType->price;
        $this->resetValidation();
    }

    public function delete(int $fileTypeId): void
    {
        $fileType = FileType::findOrFail($fileTypeId);

        if ($fileType->patients()->exists()) {
            $fileType->delete();
            $message = 'File type is in use, so it was archived and can be restored later.';
        } else {
            $fileType->delete();
            $message = 'File type deleted successfully.';
        }

        if ($this->editingFileTypeId === $fileTypeId) {
            $this->resetForm();
        }

        $this->dispatch('toast', message: $message, type: 'warning');
    }

    public function restore(int $fileTypeId): void
    {
        $fileType = FileType::withTrashed()->findOrFail($fileTypeId);
        $fileType->restore();

        $this->dispatch('toast', message: 'File type restored successfully.', type: 'success');
    }

    public function resetForm(): void
    {
        $this->reset(['editingFileTypeId', 'name', 'price']);
        $this->resetValidation();
    }

    public function render()
    {
        $query = FileType::query()
            ->when($this->status === 'deleted', fn ($query) => $query->onlyTrashed())
            ->when($this->status === 'all', fn ($query) => $query->withTrashed())
            ->when($this->search !== '', function ($query) {
                $search = trim($this->search);
                $query->where('name', 'like', "%{$search}%");
            });

        return view('components.admin.file-type-management', [
            'fileTypes' => $query->orderBy('name')->paginate(15),
            'activeCount' => FileType::count(),
            'deletedCount' => FileType::onlyTrashed()->count(),
        ]);
    }
}

