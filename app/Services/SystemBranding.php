<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SystemBranding
{
    public function settings(): ?SystemSetting
    {
        return Schema::hasTable('system_settings') ? SystemSetting::find(1) : null;
    }

    public function logoUrl(): string
    {
        $settings = $this->settings();

        return $settings?->logo_path
            ? route('branding.logo', ['v' => substr(hash('sha256', $settings->logo_path), 0, 12)])
            : asset('images/logo.png');
    }

    public function template(string $choice = 'auto', ?string $plan = null): array
    {
        $key = $choice === 'auto' ? ($plan ?? app(LicenseService::class)->currentPlan()) : $choice;
        $templates = config('welcome_templates');
        $key = isset($templates[$key]) ? $key : 'hospital';

        return ['key' => $key] + $templates[$key];
    }

    public const ARTWORK = ['background' => 'Welcome background', 'hero' => 'Main welcome image', 'supporting' => 'Supporting graphic'];

    public function imageUrl(string $slot): ?string
    {
        $path = $this->settings()?->welcome_appearance['images'][$slot]['path'] ?? null;

        return isset(self::ARTWORK[$slot]) && $path && str_starts_with($path, 'branding/')
            && Storage::disk('local')->exists($path)
            ? route('branding.image', ['slot' => $slot, 'v' => substr(hash('sha256', $path), 0, 12)]) : null;
    }

    public function colors(array $template, array $colors = []): array
    {
        $defaults = ['accent' => $template['accent'], 'background' => $template['background'], 'text' => '#172c35'];
        foreach ($defaults as $key => $default) {
            $defaults[$key] = preg_match('/^#[a-fA-F0-9]{6}$/D', $colors[$key] ?? '') ? $colors[$key] : $default;
        }

        return $defaults;
    }

    public function apply(): void
    {
        if ($settings = $this->settings()) {
            config(['app.name' => $settings->brand_name, 'app.title' => $settings->brand_name, 'app.address' => $settings->address ?? '']);
        }
    }
}
