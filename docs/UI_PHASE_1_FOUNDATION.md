# Phase 1: Modern UI Foundation

## Objective

Establish the modern UI shell and migration contract before converting the rest of Mediflow. Phase 1 does not replace every screen. It creates the structure that each module can safely move into.

## Completed Foundation

- `layouts.modern` added for migrated screens.
- Modern topbar added.
- Modern module-aware sidebar added.
- Sidebar supports desktop collapse into an icon rail.
- Sidebar supports independent scrolling.
- Sidebar uses module licensing, user module access, route validity, roles, and permissions.
- Tailwind entry files added:
  - `resources/css/modern.css`
  - `resources/js/modern.js`
- Temporary fallback stylesheet added:
  - `public/css/modern-fallback.css`
- Reusable UI components added:
  - `x-ui.page`
  - `x-ui.card`
  - `x-ui.button`
  - `x-ui.input`
  - `x-ui.select`
  - `x-ui.textarea`
  - `x-ui.badge`
  - `x-ui.table`
  - `x-ui.empty-state`
- Admin dashboard is the first trial screen using the modern layout.

## Phase 1 Rules

- Keep `layouts.app` and `layouts.live` available for existing screens.
- Use `layouts.modern` only for screens that have been visually migrated.
- Do not convert business logic during UI migration unless required.
- Do not add Bootstrap grid/classes to modern screens.
- Use `x-ui.*` components for repeated UI patterns.
- Keep module visibility controlled by the sidebar/module licensing services.
- Do not add sidebar links manually inside the modern sidebar.

## Livewire Page Contract

Every migrated Livewire component should use:

```php
use Livewire\Attributes\Layout;

#[Layout('layouts.modern')]
class ExampleComponent extends Component
{
    public function render()
    {
        return view('components.example', [
            'pageTitle' => 'Example',
            'pageSubtitle' => 'Short description of this workspace',
        ]);
    }
}
```

The Blade view should use:

```blade
<x-ui.page :title="$pageTitle" :subtitle="$pageSubtitle">
    ...
</x-ui.page>
```

## Blade Controller View Contract

Migrated controller views should use:

```blade
@extends('layouts.modern')

@section('title', 'Patients')
@section('page-title', 'Patients')
@section('page-subtitle', 'Patient records and visits')

@section('content')
    <x-ui.page title="Patients" subtitle="Patient records and visits">
        ...
    </x-ui.page>
@endsection
```

## Asset Contract

Modern screens prefer Vite assets:

- `resources/css/modern.css`
- `resources/js/modern.js`

Until Node/NPM is available and the Vite build is generated, `layouts.modern` falls back to:

- `public/css/modern-fallback.css`
- existing built `resources/js/app.js`

After Node/NPM is available, run:

```bash
npm run build
```

Then confirm `public/build/manifest.json` contains:

- `resources/css/modern.css`
- `resources/js/modern.js`

## Verification Checklist

Run after each Phase 1 foundation change:

```bash
php artisan view:cache
php artisan route:list --except-vendor
php artisan view:clear
```

For changed PHP files, also run:

```bash
php -l path/to/file.php
```

## Ready For Phase 2 When

- `/admin` renders correctly for a hospital administrator.
- Sidebar can expand/collapse on desktop.
- Sidebar scrolls when menu items overflow.
- Topbar shows the correct page title/subtitle.
- No missing Vite manifest error occurs.
- Blade compilation passes.
- The team agrees to start migrating Admin/Core screens.
