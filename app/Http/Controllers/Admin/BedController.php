<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bed;
use App\Models\Ward;

class BedController extends Controller
{
    public function index($wardId) {
        return view('admin.wards.beds.index', [
            'ward' => Ward::with(['beds' => fn ($query) => $query->orderBy('bed_no')])->findOrFail($wardId),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function create(Ward $ward) {
        return view('admin.wards.beds.create', [
            'ward' => $ward,
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function edit($bedId) {
        return view('admin.wards.beds.edit', [
            'bed' => Bed::with('ward')->findOrFail($bedId),
            'routePrefix' => $this->routePrefix(),
        ]);
    }

    public function store(Request $request, Ward $ward) {
        $request->validate(['bed_no'=>'required']);

        $bed = Bed::where('bed_no', $request->bed_no)->first();
        if($bed){
            $message = 'Bed Exist';
        }else{
            $ward->beds()->create(['bed_no'=>$request->bed_no]);
            $message = 'Bed Registered';
        }

        

        return redirect()->route($this->routePrefix().'.beds.index', $ward)->with('success', $message);
    }

    public function update(Request $request, Bed $bed) {
        $request->validate(['bed_no'=>'required']);

       $b = Bed::where('bed_no', $request->bed_no)->first();
        if($b){
            $message = 'Bed '.$request->bed_no.' Exist in '.$bed->ward->name;
        }else{
            $bed->update(['bed_no'=>$request->bed_no]);
            $message = 'Bed No Updated';
        }

        return redirect()->route($this->routePrefix().'.beds.index', $bed->ward)->with('success', $message);
    }

    public function destroy(bed $bed) {
        
        if($bed->admissions()->count() > 0){
            $message = 'The Bed already has admission record';
        }else{
            $bed->delete();
            $message = 'Bed Deleted';
        }
        

        return redirect()->route($this->routePrefix().'.beds.index', $bed->ward)->with('success', $message);
    }

    private function routePrefix(): string
    {
        $routeName = request()->route()?->getName() ?? '';

        return str_starts_with($routeName, 'medical-director.') ? 'medical-director' : 'admin';
    }
}
