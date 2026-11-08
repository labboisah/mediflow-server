<?php

namespace App\Livewire\Clinical;

use App\Livewire\Clinical\Concerns\ManagesClinicalVisit;
use App\Models\DrugChart;
use App\Models\Patient;
use App\Models\PrescriptionItem;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.modern')]

class DrugChartWorkspace extends Component
{
    use ManagesClinicalVisit;

    public string $prescriptionItemId = '';
    public string $dosage = '';
    public string $comment = '';
    public string $date;
    public string $time;
    public string $prescriptionSearch = '';
    public array $selectedItems = [];
    public ?int $editingId = null;

    public function mount(Patient $patient): void
    {
        $this->patient = $patient;
        $this->date = now()->toDateString();
        $this->time = now()->format('H:i: A');
    }

    public function render()
    {
        $items = PrescriptionItem::with(['medicine', 'route'])
            ->whereHas('prescription', fn ($query) => $query->where('patient_visit_id', $this->currentVisit()->id))
            ->where('medication_status', PrescriptionItem::STATUS_STARTED)
            ->when(trim($this->prescriptionSearch) !== '', function ($query): void {
                $search = '%' . trim($this->prescriptionSearch) . '%';
                $query->whereHas('medicine', fn ($medicineQuery) => $medicineQuery
                    ->where('name', 'like', $search)
                    ->orWhere('generic_name', 'like', $search)
                    ->orWhere('manufacturer', 'like', $search));
            })
            ->latest()
            ->get();

        return view('components.clinical.drug-chart-workspace', [
            'items' => $items,
            'recent' => $this->currentVisit()->prescriptions()
                ->with('prescriptionItems.drugCharts.medicine')
                ->latest()
                ->get()
                ->flatMap(fn ($prescription) => $prescription->prescriptionItems->flatMap->drugCharts)
                ->take(10),
        ]);
    }

    public function toggleItem(int $itemId): void
    {
        abort_unless(auth()->user()->hasAnyRole(['doctor', 'nurse', 'midwife']), 403);

        $item = $this->availableItemsQuery()->with('medicine')->findOrFail($itemId);
        $key = (string) $item->id;

        if (isset($this->selectedItems[$key])) {
            unset($this->selectedItems[$key]);
            return;
        }

        $this->selectedItems[$key] = [
            'prescription_item_id' => $item->id,
            'medicine' => $item->medicine?->name,
            'dosage' => $item->dosage ?? '',
            'comment' => '',
        ];
    }

    public function removeSelectedItem(string $key): void
    {
        abort_unless(auth()->user()->hasAnyRole(['doctor', 'nurse', 'midwife']), 403);
        unset($this->selectedItems[$key]);
    }

    public function save(): void
    {
        if (! $this->editingId && $this->selectedItems !== []) {
            $this->saveSelectedItems();
            return;
        }

        if ($this->editingId && $this->selectedItems !== []) {
            $selectedItem = reset($this->selectedItems);
            $this->prescriptionItemId = (string) $selectedItem['prescription_item_id'];
            $this->dosage = (string) $selectedItem['dosage'];
            $this->comment = (string) ($selectedItem['comment'] ?? '');
        }

        $validated = $this->validate([
            'prescriptionItemId' => ['required', 'integer', 'exists:prescription_items,id'],
            'dosage' => ['required', 'string', 'max:255'],
            'time' => ['required'],
            'date' => ['required'],
            'comment' => ['nullable', 'string', 'max:255'],
        ]);

        $item = PrescriptionItem::with('medicine', 'prescription')
            ->whereHas('prescription', fn ($query) => $query->where('patient_visit_id', $this->currentVisit()->id))
            ->where('medication_status', PrescriptionItem::STATUS_STARTED)
            ->findOrFail($validated['prescriptionItemId']);

        $payload = [
            'dosage' => $validated['dosage'],
            'medicine_id' => $item->medicine_id,
            'route_id' => $item->route_id,
            'comment' => $validated['comment'] ?? null,
            'time' =>$validated['time'],
            'date' => $validated['date'],
        ];

        if ($this->editingId) {
            $chart = DrugChart::whereHas('prescriptionItem.prescription', fn ($query) => $query->where('patient_visit_id', $this->currentVisit()->id))
                ->findOrFail($this->editingId);
            $chart->update($payload + ['prescription_item_id' => $item->id]);
            $this->logActivity("Drug chart entry updated for medicine: {$item->medicine?->name}");
        } else {
            $item->drugCharts()->create($payload + [
                'dispensed_by' => auth()->id(),
            ]);
            $this->logActivity("Drug chart entry recorded for medicine: {$item->medicine?->name}");
        }

        $this->editingId = null;
        $this->reset(['prescriptionItemId', 'dosage', 'comment', 'time', 'date']);
        $this->feedback('Drug chart saved successfully.');
    }

    private function saveSelectedItems(): void
    {
        $rules = [
            'date' => ['required', 'date'],
            'time' => ['required'],
        ];

        foreach (array_keys($this->selectedItems) as $key) {
            $rules["selectedItems.{$key}.dosage"] = ['required', 'string', 'max:255'];
            $rules["selectedItems.{$key}.comment"] = ['nullable', 'string', 'max:255'];
        }

        $validated = $this->validate($rules);
        $items = $this->availableItemsQuery()->with('medicine')->whereIn('id', array_keys($this->selectedItems))->get();

        foreach ($items as $item) {
            $selected = $validated['selectedItems'][(string) $item->id];
            $item->drugCharts()->create([
                'medicine_id' => $item->medicine_id,
                'route_id' => $item->route_id,
                'dosage' => $selected['dosage'],
                'comment' => $selected['comment'] ?? null,
                'time' => $validated['time'],
                'date' => $validated['date'],
                'dispensed_by' => auth()->id(),
            ]);
            $this->logActivity("Drug chart entry recorded for medicine: {$item->medicine?->name}");
        }

        $this->selectedItems = [];
        $this->prescriptionSearch = '';
        $this->feedback('Drug chart entries saved successfully.');
    }

    public function edit(int $id): void
    {
        $chart = DrugChart::whereHas('prescriptionItem.prescription', fn ($query) => $query->where('patient_visit_id', $this->currentVisit()->id))
            ->findOrFail($id);

        $this->editingId = $chart->id;
        $this->prescriptionItemId = (string) $chart->prescription_item_id;
        $this->dosage = (string) $chart->dosage;
        $this->comment = (string) $chart->comment;
        $this->time = (string) $chart->time;
        $this->date = (string) $chart->date;
        $this->selectedItems = [(string) $chart->prescription_item_id => [
            'prescription_item_id' => $chart->prescription_item_id,
            'medicine' => $chart->medicine?->name,
            'dosage' => $this->dosage,
            'comment' => $this->comment,
        ]];
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->selectedItems = [];
        $this->reset(['prescriptionItemId', 'dosage', 'comment','time', 'date']);
        $this->resetValidation();
    }

    private function availableItemsQuery()
    {
        return PrescriptionItem::query()
            ->whereHas('prescription', fn ($query) => $query->where('patient_visit_id', $this->currentVisit()->id))
            ->where('medication_status', PrescriptionItem::STATUS_STARTED);
    }
}
