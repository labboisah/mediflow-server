<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Medicine;
use App\Models\MedicineType;

class MedicineController extends Controller
{
    public function index() {
        $medicines = Medicine::with(['medicineType', 'batches'])->orderBy('name')->get();
        return view('pharmacy.medicine.index', compact('medicines'));
    }

    public function create() {
        $types = MedicineType::orderBy('name')->get();
        return view('pharmacy.medicine.create', compact('types'));
    }

    public function store(Request $request) {
        $data = $request->validate([
            'medicine_type_id' => ['required', 'exists:medicine_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'form' => ['nullable', 'string', 'max:255'],
            'strength' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
        ]);

        Medicine::create($data);

        return redirect()->route('pharmacy.medicines.index')->with('success', 'Medicine registered');
    }
}
