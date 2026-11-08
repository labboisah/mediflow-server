<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bill;
use App\Models\Service;
use App\Models\BillService;

class BillServiceController extends Controller
{
    public function create(Bill $bill) {
        return view('admin.bill.services.create', compact('bill'));
    }

    public function store(Request $request, Bill $bill) {
        $service = Service::find($request->input('service_id'));
        $bill->billServices()->create([
            'service_id' => $request->input('service_id'),
            'unit_price' => $service->price,
            'quantity' => $request->input('quantity'),
            'subtotal' => $service->price * $request->input('quantity'),
        ]);
        return redirect()->route('admin.bills.show', $bill)->with('success', 'Service added successfully.');
    }

    public function edit($billService) {
        $billService = BillService::find($billService);
        $bill = $billService->bill;
        return view('admin.bill.services.edit', compact('billService', 'bill'));
    }

    public function update(Request $request, $billService) {
        $billService = BillService::find($billService);
        $service = Service::find($request->input('service_id'));

        $billService->update([
            'service_id' => $request->input('service_id'),
            'unit_price' => $service->price,
            'quantity' => $request->input('quantity'),
            'subtotal' => $service->price * $request->input('quantity'),
        ]);
        return redirect()->route('admin.bills.show', $billService->bill)->with('success', 'Service updated successfully.');
    }   

    public function destroy($billService) {
        $billService = BillService::find($billService);
        $bill = $billService->bill;
        $billService->delete();
        return redirect()->route('admin.bills.show', $bill)->with('success', 'Service deleted successfully.');
    }
}
