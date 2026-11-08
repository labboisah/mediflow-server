<?php

namespace App\Http\Controllers;

use App\Services\WifiSharingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WifiSharingController extends Controller
{
    public function status(Request $request, WifiSharingService $wifiSharing): JsonResponse
    {
        abort_unless($wifiSharing->canManage($request), 403);

        return response()->json($wifiSharing->status());
    }

    public function connect(Request $request, WifiSharingService $wifiSharing): JsonResponse
    {
        abort_unless($wifiSharing->canManage($request), 403);

        $connected = $wifiSharing->start();

        return response()->json($wifiSharing->status(), $connected ? 200 : 500);
    }
}
