<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ward;

class WardController extends Controller
{
    public function index() {
        return view('admin.wards.index', [
            'wards' => Ward::query()
                ->withCount([
                    'beds',
                    'beds as occupied_beds_count' => fn ($query) => $query->where('status', 'occupied'),
                    'beds as vacant_beds_count' => fn ($query) => $query->where('status', 'vacant'),
                ])
                ->orderBy('name')
                ->get(),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function create() {
        return view('admin.wards.create', [
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function edit($wardId) {
        return view('admin.wards.edit', [
            'ward' => Ward::findOrFail($wardId),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function show(Ward $ward) {
        return redirect()->route($this->routePrefix().'.wards.edit', $ward);
    }

    public function store(Request $request) {
        $request->validate([
            'name'=>'required',
            'capacity'=>'required',
            'price'=>'required',
            ]);

        $ward = Ward::firstOrCreate([
            'name'=>$request->name,
            'price'=>$request->price,
            'capacity'=>$request->capacity,
            ]);

            for($capacity = 1; $capacity <= $ward->capacity; $capacity++){
                $ward->beds()->create(['bed_no'=>$this->format($capacity)]);
            }

        return redirect()->route($this->routePrefix().'.wards.index')->with('success', 'Ward Registered');
    }

    public function update(Request $request, Ward $ward) {
        $request->validate([
            'name'=>'required',
            'capacity'=>'required',
            'price'=>'required',
            ]);

        $ward->update([
            'name'=>$request->name,
            'price'=>$request->price,
            'capacity'=>$request->capacity,
            ]);

            foreach($ward->beds as $bed){
                $bed->delete();
            }

            for($capacity = 1; $capacity <= $ward->capacity; $capacity++){
                $ward->beds()->create(['bed_no'=>$this->format($capacity)]);
            }

        return redirect()->route($this->routePrefix().'.wards.index')->with('success', 'Ward Updated');
    }

    public function destroy(ward $ward) {
        
        foreach($ward->beds as $bed){
            $bed->delete();
        }

        $ward->delete();

        return redirect()->route($this->routePrefix().'.wards.index')->with('success', 'Ward Deleted');
    }

    private function format($number) {
        if($number <= 9){
            $number = '0'.$number;
        }
        return $number;
    }

    private function routePrefix(): string
    {
        $routeName = request()->route()?->getName() ?? '';

        return str_starts_with($routeName, 'medical-director.') ? 'medical-director' : 'admin';
    }
}
