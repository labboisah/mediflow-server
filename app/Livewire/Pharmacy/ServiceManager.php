<?php

namespace App\Livewire\Pharmacy;

use App\Models\PharmacyService;
use App\Services\LicenseService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.modern')]
class ServiceManager extends Component
{
    #[Locked]
    public ?int $editingId = null;
    public string $name = '';
    public string $price = '';
    public bool $isActive = true;

    public function boot(): void
    {
        abort_unless(PharmacyService::canManage(auth()->user())
            && app(LicenseService::class)->moduleEnabled('pharmacy'), 403);
    }

    public function edit(int $id): void
    {
        $service = PharmacyService::findOrFail($id);
        $this->editingId = $service->id;
        $this->name = $service->name;
        $this->price = $service->price;
        $this->isActive = $service->is_active;
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->reset(['editingId', 'name', 'price', 'isActive']);
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->name = trim($this->name);
        $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('pharmacy_services', 'name')->ignore($this->editingId)],
            'price' => ['required', 'numeric', 'min:0.01', 'max:9999999.99', 'decimal:0,2'],
            'isActive' => ['boolean'],
        ]);
        $service = $this->editingId ? PharmacyService::findOrFail($this->editingId) : new PharmacyService;
        $service->fill(['name' => $this->name, 'price' => $this->price, 'is_active' => $this->isActive])->save();
        $this->cancel();
        session()->flash('serviceSaved', 'Service saved. New charges apply to new transactions.');
    }

    public function render()
    {
        return view('components.pharmacy.service-manager', ['services' => PharmacyService::orderBy('name')->get()]);
    }
}
