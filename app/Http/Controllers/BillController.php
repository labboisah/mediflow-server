<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\BillItem;
use App\Models\Bill;
use App\Services\RevenueCalculator;

class BillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bills = \App\Models\Bill::withCount('results')->latest()->paginate(15);

        return view('bills.index', compact('bills'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $services = Service::orderBy('name')->get();
        return view('bills.create', compact('services'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_name' => 'nullable|string',
            'services' => 'required|array',
            'services.*' => 'exists:services,id',
        ]);

        $billNo = $this->generateBillNo();

        $totalAmount = 0;
        $totalStaffShare = 0;
        $totalAnnexShare = 0;

        $bill = Bill::create([
            'bill_no' => $billNo,
            'patient_name' => $request->patient_name,
            'user_id' => auth()->id(),
        ]);

        foreach ($request->services as $serviceId) {

            $service = \App\Models\Service::find($serviceId);

            $price = $service->price;
            $rule = $service->category->revenueRule;
            $staffShare = $price * ($rule->staff_percent / 100);
            $annexShare = $price * ($rule->annex_percent / 100);
            $shares = RevenueCalculator::calculate($service, $price);
            $billItem = BillItem::create([
                'bill_id' => $bill->id,
                'service_id' => $service->id,
                'price' => $price,
            ]);

            $totalAmount += $price;
            $totalStaffShare += $staffShare;
            $totalAnnexShare += $annexShare;

            $billItem->revenueDistribution()->create([
                'radiologist_amount' => $shares['radiologist'],
                'radiographer_amount' => $shares['radiographer'],
                'staff_amount' => $shares['staff'],
                'annex_amount' => $shares['annex']
            ]);
        }

        $discount = $request->discount ?? 0;
        $finalAmount = $totalAmount - $discount;

        $bill->update([
            'total_amount' => $totalAmount,
            'discount_amount' => $discount,
            'final_amount' => $finalAmount,
            'total_paid' => 0,
            'balance' => $finalAmount,
            'payment_status' => 'unpaid',
        ]);

        return redirect()->route('payments.create',$bill)
            ->with('success', 'Bill recorded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\Bill $bill)
    {
        $bill->load('items.service', 'user')->loadCount('results');

        return view('bills.show', compact('bill'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bill $bill)
    {
        if ($bill->results()->exists()) {
            return back()->with('error', 'This bill cannot be deleted because a result has already been entered.');
        }

        $bill->delete();

        return redirect()
            ->route('bills.index')
            ->with('success', 'Bill deleted successfully.');
    }

    private function generateBillNo(): string
    {
        do {
            $billNo = 'B' . now()->format('ymd') . '-' . random_int(1000, 9999);
        } while (Bill::where('bill_no', $billNo)->exists());

        return $billNo;
    }
}
