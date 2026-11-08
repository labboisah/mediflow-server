<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bill;

class BillController extends Controller
{
    public function index(Request $request) {
        if ($request->ajax()) {
            $start = (int) $request->get('start', 0);
            $length = (int) $request->get('length', 10);
            $search = $request->input('search.value');
            $orderCol = (int) $request->input('order.0.column', 10);
            $orderDir = $request->input('order.0.dir', 'desc');

            $columns = [
                0 => null,
                1 => 'bill_number',
                2 => null,
                3 => 'service_description',
                4 => 'amount',
                5 => 'discount',
                6 => 'due_amount',
                7 => 'status',
                8 => null,
                9 => 'issued_by',
                10 => 'created_at',
                11 => null,
            ];

            $query = Bill::with(['patientVisit.patient', 'walkinPatient', 'issuedBy']);
            $recordsTotal = $query->count();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('bill_number', 'like', "%{$search}%")
                        ->orWhere('service_description', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('walkinPatient', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('patientVisit.patient', function ($q) use ($search) {
                            $q->whereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]);
                        })
                        ->orWhereHas('issuedBy', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            }

            $recordsFiltered = $query->count();

            $sortColumn = $columns[$orderCol] ?? 'created_at';
            if ($sortColumn) {
                $query->orderBy($sortColumn, $orderDir);
            } else {
                $query->orderBy('created_at', 'desc');
            }

            $bills = $query->skip($start)->take($length)->get();

            $data = $bills->values()->map(function ($bill, $index) use ($start) {
                $patient = $bill->walkinPatient ? $bill->walkinPatient->name : ($bill->patientVisit?->patient?->name() ?? 'N/A');
                $actions = '<a href="'.route('admin.bills.show', $bill).'" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a> '
                    .'<a href="'.route('admin.bills.edit', $bill).'" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a> '
                    .'<a href="'.route('admin.bills.delete', $bill).'" class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash"></i></a>';

                return [
                    $start + $index + 1,
                    $bill->bill_number,
                    $patient,
                    $bill->service_description,
                    number_format($bill->amount, 2),
                    number_format($bill->discount, 2),
                    number_format($bill->due_amount, 2),
                    ucfirst($bill->status),
                    $bill->isAmountConsistent() ? '<span class="text-success"><i class="fas fa-check-circle"></i> Consistent</span>' : '<span class="text-danger"><i class="fas fa-exclamation-triangle"></i> Inconsistent</span>',
                    $bill->issuedBy->name ?? 'N/A',
                    $bill->created_at?->format('Y-m-d H:i') ?? '',
                    $actions,
                ];
            })->toArray();

            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
            ]);
        }

        return view('admin.bill.index');
    }

    public function show($bill) {
        // Logic to show a specific bill
        $bill = Bill::find($bill);
        return view('admin.bill.show', compact('bill'));
    }

    public function edit($bill) {
        $bill = Bill::find($bill);
        return view('admin.bill.edit', compact('bill'));
    }

    public function update(Request $request, $bill) {
        $bill = Bill::find($bill);
        $bill->update([
            'amount' => $request->input('amount'),
            'due_amount' => $request->input('amount') - ($request->input('amount') * $request->input('discount') / 100),
            'discount' => $request->input('discount'),
            'due_date' => $request->input('due_date'),
            'issued_date' => $request->input('issued_date'),
            'status' => $request->input('status'),
        ]);
        return redirect()->route('admin.bills.show', $bill)->with('success', 'Bill updated successfully.');
    }

    public function destroy($bill) {
        $bill = Bill::find($bill);
        $bill->delete();
        return redirect()->route('admin.bills.index')->with('success', 'Bill deleted successfully.');
    }

    // bill services management
    public function storeService(Request $request, $bill) {
        $bill = Bill::find($bill);
        $service = Service::find($request->input('service_id'));
        $bill->services()->create([
            'service_id' => $request->input('service_id'),
            'unit_price' => $service->price,
            'quantity' => $request->input('quantity'),
            'subtotal' => $service->price * $request->input('quantity'),
        ]);
        return redirect()->route('admin.bills.show', $bill)->with('success', 'Service added successfully.');
    }

    public function destroyService($bill, $service) {
        $bill = Bill::find($bill);
        $bill->services()->detach($service);
        return redirect()->route('admin.bills.show', $bill)->with('success', 'Service removed successfully.');
    }

    // update bill service
    public function updateService(Request $request, $bill, $billService) {
        $bill = Bill::find($bill);
        dd($billService);
        $BIservice = Service::find($request->input('service_id'));
        $bill->services()->updateExistingPivot($service->id, [
            'unit_price' => $service->price,
            'quantity' => $request->input('quantity'),
            'subtotal' => $service->price * $request->input('quantity'),
        ]);
        return redirect()->route('admin.bills.show', $bill)->with('success', 'Service updated successfully.');
    }

}
