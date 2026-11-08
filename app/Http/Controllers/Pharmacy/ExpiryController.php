<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MedicineBatch;

class ExpiryController extends Controller
{
    public function index() {
        $batches = MedicineBatch::latest()->get();
       return view('pharmacy.expiry.index', compact('batches')); 
    }
}
