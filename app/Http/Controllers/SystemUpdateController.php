<?php

namespace App\Http\Controllers;

use App\Services\UpdateService;

class SystemUpdateController extends Controller
{
    public function index(UpdateService $updateService)
    {
        $local = $updateService->getLocalCommit();
        $remote = $updateService->getRemoteCommit();
        
        return view('admin.update', [
            'hasUpdate' => filled($remote) && filled($local) && $remote !== $local,
            'local' => $local,
            'remote' => $remote,
            'connectionError' => blank($remote) ? 'Unable to connect to GitHub. Please check your internet connection.' : null,
        ]);
    }

    public function update()
    {
        return response()->json(app(UpdateService::class)->update());
    }
}
