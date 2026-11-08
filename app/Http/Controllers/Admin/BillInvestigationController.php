<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\Service;
use App\Models\Investigation;
use App\Models\BillInvestigation;

class BillInvestigationController extends Controller
{
    public function create($billId) {
        // Logic to show form for creating a new investigation for the bill
        $bill = Bill::findOrFail($billId);
        return view('admin.bill.investigations.create', compact('bill'));
    }

    public function store(Request $request, $bill) {
        // Logic to store a new investigation for the bill
        $bill = Bill::findOrFail($bill);
        $investigation = Investigation::findOrFail($request->input('investigation_id'));
        $bill->billInvestigations()->create([
            'investigation_id' => $investigation->id,
            'unit_price' => $investigation->price,
            'quantity' => $request->input('quantity'),
            'subtotal' => $investigation->price * $request->input('quantity'),
        ]);
        return redirect()->route('admin.bills.show', $bill)->with('success', 'Investigation added successfully.');
    }

    public function edit($billInvestigation) {
        // Logic to show form for editing an existing investigation for the bill
        $billInvestigation = BillInvestigation::findOrFail($billInvestigation);
        $bill = $billInvestigation->bill;
        return view('admin.bill.investigations.edit', compact('billInvestigation', 'bill'));
    }

    public function update(Request $request, $billInvestigation) {
        // Logic to update an existing investigation for the bill
        $billInvestigation = BillInvestigation::findOrFail($billInvestigation);
        $investigation = Investigation::findOrFail($request->input('investigation_id'));
        $billInvestigation->update([
            'investigation_id' => $investigation->id,
            'unit_price' => $investigation->price,
            'quantity' => $request->input('quantity'),
            'subtotal' => $investigation->price * $request->input('quantity'),
        ]);
        return redirect()->route('admin.bills.show', $billInvestigation->bill)->with('success', 'Investigation updated successfully.');
    }

    public function destroy($billInvestigation) {
        // Logic to delete an existing investigation for the bill
        $billInvestigation = BillInvestigation::findOrFail($billInvestigation);
        $billInvestigation->delete();
        return redirect()->route('admin.bills.show', $billInvestigation->bill)->with('success', 'Investigation deleted successfully.');
    }
}
