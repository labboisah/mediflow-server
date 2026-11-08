<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Consumable;
use App\Models\ConsumableStock;

class ConsumableStockController extends Controller
{

    public function index()
    {
        $stocks = ConsumableStock::with('consumable')->latest()->get();

        return view('department.stocks.index',compact('stocks'));
    }

    public function create()
    {
        $consumables = auth()->user()->department->consumables;

        return view('department.stocks.create',compact('consumables'));
    }

    public function store(Request $request)
    {

        ConsumableStock::create($request->all());

        return redirect()->route('department.stocks.index');

    }

    public function edit(ConsumableStock $consumableStock)
    {
        $consumables = auth()->user()->department->consumables;
       
        return view('department.stocks.edit',
            compact('consumableStock','consumables'));
    }

    public function update(Request $request, ConsumableStock $consumableStock)
    {
        $consumableStock->update($request->all());

        return redirect()->route('department.stocks.index');
    }

    public function destroy(ConsumableStock $consumableStock)
    {
        $consumableStock->delete();

        return back();
    }

}
