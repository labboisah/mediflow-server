<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Investigation;
use App\Models\Bed;
use App\Models\Medicine;
use App\Models\Lga;

class AjaxController extends Controller
{
    function getInvestigations($typeId) {
       
        return response()->json(
            Investigation::where('investigation_type_id', $typeId)
                ->orderBy('name')
                ->get(['id', 'name'])
        );
       
    }

    function getWardBeds($wardId) {
       
        return response()->json(
            Bed::where('ward_id', $wardId)->where('status', 'vacant')
                ->orderBy('bed_no')
                ->get(['id', 'bed_no', 'status'])
        );
       
    }

    function getLgas($stateId) {
       
        return response()->json(Lga::where('state_id', $stateId)->pluck('name','id'));
       
    }

    function getMedicines($medicineTypeId) {
       
        return response()->json(
            Medicine::with('batches')
                ->where('medicine_type_id', $medicineTypeId)
                ->orderBy('name')
                ->get()
                ->map(fn (Medicine $medicine) => [
                    'id' => $medicine->id,
                    'name' => $medicine->name,
                    'generic_name' => $medicine->generic_name,
                    'manufacturer' => $medicine->manufacturer,
                    'display_name' => $medicine->displayName(),
                    'available_quantity' => $medicine->availableQuantity(),
                    'available' => $medicine->availableQuantity() > 0,
                    'selling_price' => $medicine->latestSellingPrice(),
                ])
        );
       
    }
}
