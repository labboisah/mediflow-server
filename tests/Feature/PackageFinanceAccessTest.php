<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureInstallationModules;
use App\Livewire\Admin\ExpenseCategoryManagement;
use App\Livewire\Admin\ExpenseManagement;
use App\Livewire\Admin\RevenueCategoryManagement;
use App\Livewire\Admin\RevenueManagement;
use App\Models\User;
use App\Services\LicenseService;
use App\Services\PackageFinanceAccess;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PackageFinanceAccessTest extends TestCase
{
    private function user(bool $admin): User
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')->with('administrator')->andReturn($admin);

        return $user;
    }

    public function test_licensed_administrator_has_access_but_other_staff_do_not(): void
    {
        $this->mock(LicenseService::class, fn ($m) => $m->shouldReceive('moduleEnabled')->with('finance')->andReturn(true));
        $access = app(PackageFinanceAccess::class);
        $this->assertTrue($access->allows($this->user(true)));
        $this->assertFalse($access->allows($this->user(false)));
        $this->assertFalse($access->allows(null));
    }

    public function test_administrator_cannot_bypass_finance_entitlement(): void
    {
        $this->mock(LicenseService::class, fn ($m) => $m->shouldReceive('moduleEnabled')->with('finance')->andReturn(false));
        $this->assertFalse(app(PackageFinanceAccess::class)->allows($this->user(true)));
    }

    public function test_non_administrator_cannot_use_alternate_finance_route_or_export(): void
    {
        foreach (['admin.expenses.index', 'admin.revenues.pdf', 'finance.expenses.pdf', 'medical-director.revenues.index', 'admin.expense-categories.index'] as $name) {
            $request = Request::create('/test');
            $route = new Route('GET', 'test', fn () => null);
            $route->name($name);
            $request->setRouteResolver(fn () => $route);
            $request->setUserResolver(fn () => $this->user(false));
            try {
                (new EnsureInstallationModules)->handle($request, fn () => new Response('forbidden'));
                $this->fail('Unlicensed role reached '.$name);
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
    }

    public function test_livewire_finance_components_check_access_on_every_request(): void
    {
        foreach ([ExpenseManagement::class, RevenueManagement::class, ExpenseCategoryManagement::class, RevenueCategoryManagement::class] as $class) {
            try {
                (new $class)->boot();
                $this->fail('Unauthenticated component access');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
    }
}
