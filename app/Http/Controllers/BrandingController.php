<?php

namespace App\Http\Controllers;

use App\Services\SystemBranding;
use Illuminate\Support\Facades\Storage;

class BrandingController extends Controller
{
    public function logo(SystemBranding $branding)
    {
        $path = $branding->settings()?->logo_path;
        abort_unless($path && str_starts_with($path, 'branding/') && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'no-cache', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function image(string $slot, SystemBranding $branding)
    {
        abort_unless(isset(SystemBranding::ARTWORK[$slot]), 404);
        $path = $branding->settings()?->welcome_appearance['images'][$slot]['path'] ?? null;
        abort_unless($path && preg_match('~^branding/[a-zA-Z0-9_-]+\.(png|jpg|jpeg|webp)$~D', $path)
            && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'no-cache', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
