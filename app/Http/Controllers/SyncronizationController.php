<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SyncronizationController extends Controller
{
    public function index()
    {
        
        return redirect()->route('admin.sync.index');
    }

    public function update(Request $request)
    {
        // Implement your synchronization logic here
        // For example, you can call a service to perform the synchronization

        // Return a response indicating success or failure
        return response()->json(['message' => 'Data synchronization completed successfully.']);
    }
}

