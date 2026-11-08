<?php
namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Consumable;

class ConsumableController extends Controller
{

public function index()
{
    $consumables = Consumable::latest()->get();
    return view('department.consumables.index',compact('consumables'));
}

public function create()
{
    return view('department.consumables.create');
}

public function store(Request $request)
{
    $department = auth()->user()->department;
    $department->consumables()->create($request->all());

    return redirect()->route('department.consumables.index')
        ->with('success','Consumable created');
}

public function edit(Consumable $consumable)
{
    return view('department.consumables.edit',compact('consumable'));
}

public function update(Request $request, Consumable $consumable)
{
    $consumable->update($request->all());

    return redirect()->route('department.consumables.index');
}

public function destroy(Consumable $consumable)
{
    $consumable->delete();

    return back();
}

}