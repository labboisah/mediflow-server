<?php

namespace App\Livewire\Clinical;

use App\Livewire\Clinical\Concerns\ManagesClinicalVisit;
use App\Models\Bed;
use App\Models\Patient;
use App\Models\Ward;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.modern')]
class AdmissionWorkspace extends Component
{
    use ManagesClinicalVisit;

    public ?int $editingId = null;
    public string $wardId = '';
    public string $bedId = '';
    public string $date = '';
    public string $time = '';
    public string $days = '1';
    public string $note = '';

    public function mount(Patient $patient): void
    {
        $this->patient = $patient;
        $this->date = now()->toDateString();
        $this->time = now()->format('H:i');
    }

    public function render()
    {
        $ward = $this->wardId ? Ward::find($this->wardId) : null;
        $days = max(1, (int) $this->days);

        return view('components.clinical.admission-workspace', [
            'wards' => Ward::withCount(['beds'])->orderBy('name')->get(),
            'beds' => $this->wardId
                ? Bed::where('ward_id', $this->wardId)
                    ->where(fn ($query) => $query->where('status', 'vacant')->orWhere('id', $this->bedId))
                    ->orderBy('bed_no')
                    ->get()
                : collect(),
            'estimatedAmount' => $ward ? ((float) $ward->price * $days) : 0,
            'admissions' => $this->currentVisit()->admissions()->with(['bed.ward', 'bills.billServices'])->latest()->get(),
        ]);
    }

    public function updatedWardId(): void
    {
        $this->bedId = '';
    }

    public function save(): void
    {
        $validated = $this->validate([
            'wardId' => ['required', 'integer', 'exists:wards,id'],
            'bedId' => ['required', 'integer', 'exists:beds,id'],
            'date' => ['required', 'date'],
            'time' => ['required'],
            'days' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $visit = $this->currentVisit();
        $currentAdmission = $this->editingId
            ? $visit->admissions()->findOrFail($this->editingId)
            : null;
        $bed = Bed::with('ward')
            ->where('ward_id', $validated['wardId'])
            ->where(fn ($query) => $query
                ->where('status', 'vacant')
                ->orWhere('id', $currentAdmission?->bed_id)
            )
            ->findOrFail($validated['bedId']);

        if ($this->editingId) {
            $admission = $currentAdmission->load('bed');

            if ((int) $admission->bed_id !== (int) $bed->id) {
                $admission->bed?->update(['status' => 'vacant']);
                $bed->update(['status' => 'occupied']);
            }

            $admission->update([
                'date' => $validated['date'],
                'time' => $validated['time'],
                'bed_id' => $bed->id,
                'note' => $validated['note'],
            ]);

            $this->refreshBedSpaceBill($admission, $bed, (int) $validated['days']);
            $this->logActivity("Admission ward/bed charge updated for bed {$bed->bed_no} for {$validated['days']} days");
            $this->feedback('Admission and ward/bed charge updated.');
        } else {
            $admission = $visit->admissions()->create([
                'date' => $validated['date'],
                'time' => $validated['time'],
                'bed_id' => $bed->id,
                'note' => $validated['note'],
                'admitted_by' => auth()->id(),
            ]);

            $bed->update(['status' => 'occupied']);
            $visit->generateBedSpaceBill($admission, $bed, (int) $validated['days']);
            $this->logActivity("Patient admitted to bed {$bed->bed_no} for {$validated['days']} days");
            $this->feedback('Admission registered and bed bill created.');
        }

        $this->editingId = null;
        $this->reset(['wardId', 'bedId', 'note']);
        $this->days = '1';
        $this->date = now()->toDateString();
        $this->time = now()->format('H:i');
    }

    public function edit(int $id): void
    {
        $admission = $this->currentVisit()->admissions()->with(['bed.ward', 'bills.billServices'])->findOrFail($id);
        $bedBill = $admission->bills->firstWhere('service_description', 'Bed Space Charges') ?? $admission->bills->first();

        $this->editingId = $admission->id;
        $this->wardId = (string) $admission->bed?->ward_id;
        $this->bedId = (string) $admission->bed_id;
        $this->days = (string) ((int) ($bedBill?->billServices?->sum('quantity') ?: 1));
        $this->date = Carbon::parse($admission->date)->toDateString();
        $this->time = (string) $admission->time;
        $this->note = (string) $admission->note;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->reset(['wardId', 'bedId', 'note']);
        $this->days = '1';
        $this->date = now()->toDateString();
        $this->time = now()->format('H:i');
        $this->resetValidation();
    }

    private function refreshBedSpaceBill($admission, Bed $bed, int $days): void
    {
        $amount = (float) $bed->ward->price * $days;
        $bill = $admission->bills()->where('service_description', 'Bed Space Charges')->first()
            ?? $admission->bills()->first();

        if (! $bill) {
            $admission->patientVisit->generateBedSpaceBill($admission, $bed, $days);
            return;
        }

        $bill->update([
            'amount' => $amount,
            'due_amount' => $amount,
            'department_id' => auth()->user()->department?->id,
        ]);

        $billService = $bill->billServices()->first();
        if ($billService) {
            $billService->update([
                'unit_price' => (float) $bed->ward->price,
                'quantity' => $days,
                'subtotal' => $amount,
            ]);
        }
    }
}

