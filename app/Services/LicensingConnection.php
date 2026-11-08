<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class LicensingConnection
{
    public function normalize(string $url): string
    {
        $url = rtrim(trim($url), '/');

        return str_ends_with($url, '/api/v1') ? $url : $url.'/api/v1';
    }

    public function allowed(): array
    {
        return array_map(fn ($url) => $this->normalize($url), array_filter(array_merge([config('kernelbridge-licensing.api_url')], config('kernelbridge-licensing.allowed_servers', []))));
    }

    public function load(): void
    {
        $saved = Storage::disk('local')->get('licensing/server-url.txt');
        if ($saved && in_array($saved, $this->allowed(), true)) {
            config(['kernelbridge-licensing.api_url' => $saved]);
        }
    }

    public function select(?string $url): string
    {
        $url = $this->normalize($url ?? config('kernelbridge-licensing.api_url'));
        if (! in_array($url, $this->allowed(), true)) {
            throw ValidationException::withMessages(['server_url' => 'This server is not authorized for the saved API credential. Ask the deployment administrator to configure KERNELBRIDGE_API_URL and its matching API token, or authorize the address in KERNELBRIDGE_ALLOWED_SERVERS.']);
        }
        config(['kernelbridge-licensing.api_url' => $url]);

        return $url;
    }

    public function save(string $url): void
    {
        Storage::disk('local')->put('licensing/server-url.txt', $url);
    }
}
