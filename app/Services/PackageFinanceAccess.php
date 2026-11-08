<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

final class PackageFinanceAccess
{
    public function matches(?string $route): bool
    {
        return $route && Str::is(['admin.expenses.*', 'admin.revenues.*', 'admin.expense-categories.*', 'admin.revenue-categories.*', 'finance.expenses.*', 'finance.revenues.*', 'medical-director.expenses.*', 'medical-director.revenues.*'], $route);
    }

    public function allows(?User $user): bool
    {
        return $user && $user->hasRole('administrator') && app(LicenseService::class)->moduleEnabled('finance');
    }

    public function authorize(): void
    {
        abort_unless($this->allows(auth()->user()), 403, 'Expense and revenue management is restricted to the administrator of an installation licensed for finance.');
    }
}
