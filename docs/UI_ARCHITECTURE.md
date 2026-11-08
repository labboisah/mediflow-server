# Mediflow Modern UI Architecture

## Goal

Move Mediflow to a cleaner, module-based healthcare interface without breaking existing screens. The current Bootstrap layouts remain available while new or migrated modules use the modern Tailwind layout.

For continuation prompts and remaining migration order, see `docs/UI_MODERN_MIGRATION_CONTINUATION_GUIDE.md`.

## Stack Direction

- Laravel remains the application backend.
- Livewire remains the primary interaction layer for forms, tables, dashboards, and workflows.
- Tailwind CSS becomes the styling foundation for new screens.
- Alpine.js handles lightweight layout interactions such as sidebar, dropdowns, and toasts.
- Flux UI or WireUI can be introduced later after the base layout proves stable.

## Layouts

Current screens may continue using:

- `layouts.app`
- `layouts.live`

New or migrated screens should use:

- `layouts.modern`

For Blade controller views:

```blade
@extends('layouts.modern')

@section('title', 'Patients')
@section('page-title', 'Patients')
@section('page-subtitle', 'Patient records and visits')

@section('content')
    <x-ui.page title="Patients" subtitle="Manage patient records and visits.">
        ...
    </x-ui.page>
@endsection
```

For Livewire components:

```php
use Livewire\Attributes\Layout;

#[Layout('layouts.modern')]
class PatientManagement extends Component
{
    public function render()
    {
        return view('components.patient.patient-management', [
            'pageTitle' => 'Patients',
            'pageSubtitle' => 'Patient records and active visits',
        ]);
    }
}
```

Livewire pages should pass `pageTitle` and `pageSubtitle` from `render()` because the modern topbar is rendered by the layout before the page body is displayed. Blade controller views may continue using `@section('page-title')` and `@section('page-subtitle')`.

## Assets

Modern screens load:

- `resources/css/modern.css`
- `resources/js/modern.js`

Legacy screens continue loading:

- `resources/css/app.css`
- `resources/js/app.js`

This separation allows module-by-module migration.

## Design Tokens

The modern theme is defined in:

- `tailwind.config.js`
- `resources/css/modern.css`

Core color tokens:

- `med-ink`
- `med-muted`
- `med-line`
- `med-canvas`
- `med-surface`
- `med-primary`
- `med-primaryDark`
- `med-accent`
- `med-info`
- `med-danger`

Use these tokens instead of one-off colors.

## Sidebar

The modern sidebar uses the existing module-aware sidebar service:

- license module must be enabled
- user must have module access unless the user is a hospital administrator
- user must have role or permission for the module
- route must exist
- route must not require parameters

The partial is:

- `resources/views/layouts/partials/modern-sidebar.blade.php`

It reads from:

- `App\Services\SidebarService`
- `config/sidebar.php`
- `modules.sidebar_group`
- `modules.license_module`
- `modules.sidebar_patterns`

## Page Components

Use these first:

- `x-ui.page`
- `x-ui.card`
- `x-ui.button`
- `x-ui.input`
- `x-ui.select`
- `x-ui.textarea`
- `x-ui.badge`
- `x-ui.table`
- `x-ui.empty-state`

Example:

```blade
<x-ui.page title="Pharmacy Stock" subtitle="Track stock levels, batches, and expiry alerts.">
    <x-slot:actions>
        <x-ui.button>
            <i class="bi bi-plus-lg"></i>
            New Stock
        </x-ui.button>
    </x-slot:actions>

    <x-ui.card title="Inventory">
        <x-ui.table>
            ...
        </x-ui.table>
    </x-ui.card>
</x-ui.page>
```

## Migration Order

Recommended first modules:

1. Dashboard
2. Access Control
3. Patients
4. Pharmacy
5. Billing
6. Clinical
7. Maternity
8. Diagnostics
9. Reports

## Rules

- Do not mix Bootstrap layout classes inside newly migrated modern screens.
- Keep business logic in existing services/controllers/Livewire classes where possible.
- Convert one module at a time.
- Keep sidebar records tied to license modules and permissions.
- Use `layouts.modern` only when the screen has been visually migrated.
- Every migrated Livewire screen must define `pageTitle` and `pageSubtitle`.
- Every migrated page should compile with `php artisan view:cache`.
