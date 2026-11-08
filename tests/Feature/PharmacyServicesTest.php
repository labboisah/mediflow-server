<?php

namespace Tests\Feature;

use App\Livewire\Pharmacy\{ServiceManager, TransactionWorkspace};
use App\Models\{Bill, Department, Medicine, MedicineBatch, Payment, PaymentMethod, PharmacyService, Role, Service, StockTransaction, User};
use App\Services\PharmacyCheckout;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PharmacyServicesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'sync.behavior.auto_sync_enabled' => false]);
        Schema::create('departments', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('email'); $t->string('password'); $t->unsignedBigInteger('department_id')->nullable(); $t->boolean('is_installation_admin')->default(false); $t->timestamp('email_verified_at')->nullable(); $t->softDeletes(); $t->timestamps(); });
        Schema::create('roles', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Schema::create('role_user', function (Blueprint $t) { $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('role_id'); });
        Schema::create('medicines', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Schema::create('medicine_batches', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('medicine_id'); $t->string('batch_number'); $t->date('expiry_date'); $t->integer('quantity_remaining'); $t->decimal('selling_price', 12, 2); $t->timestamps(); });
        Schema::create('services', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('department_id')->nullable(); $t->string('code')->unique(); $t->string('name'); $t->text('description')->nullable(); $t->decimal('price', 10, 2); $t->string('category')->nullable(); $t->boolean('is_active')->default(true); $t->softDeletes(); $t->timestamps(); });
        Schema::create('payment_methods', function (Blueprint $t) { $t->id(); $t->string('name'); $t->boolean('is_active'); $t->timestamps(); });
        Schema::create('stock_transactions', function (Blueprint $t) { $t->id(); $t->decimal('total_amount', 15, 2); $t->string('type'); $t->unsignedBigInteger('created_by'); $t->string('reference')->nullable(); $t->unsignedBigInteger('bill_id')->nullable(); $t->unsignedBigInteger('payment_id')->nullable(); $t->timestamps(); });
        Schema::create('stock_transaction_items', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('transaction_id'); $t->unsignedBigInteger('medicine_batch_id'); $t->integer('quantity'); $t->decimal('price', 12, 2); $t->decimal('subtotal', 15, 2); $t->timestamps(); });
        Schema::create('pharmacy_dispenses', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('medicine_batch_id'); $t->string('type'); $t->integer('quantity'); $t->string('reference'); $t->unsignedBigInteger('created_by'); $t->timestamps(); });
        Schema::create('bills', function (Blueprint $t) {
            $t->id(); foreach (['walkin_id', 'department_id', 'issued_by'] as $c) $t->unsignedBigInteger($c)->nullable();
            $t->string('bill_number')->unique(); $t->text('service_description'); $t->decimal('amount', 12, 2); $t->decimal('due_amount', 12, 2); $t->string('status'); $t->dateTime('issued_date'); $t->dateTime('due_date'); $t->text('notes')->nullable(); $t->softDeletes(); $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t) {
            $t->id(); $t->string('payment_id')->unique(); $t->decimal('amount', 12, 2); foreach (['bill_id', 'paid_by', 'payment_method_id'] as $c) $t->unsignedBigInteger($c);
            $t->string('reference_number')->nullable(); $t->string('status'); $t->text('notes'); $t->dateTime('payment_date'); $t->softDeletes(); $t->timestamps();
        });
        (require database_path('migrations/2026_03_09_184420_create_walkin_patients_table.php'))->up();
        (require database_path('migrations/2026_09_28_000001_add_pharmacy_services.php'))->up();
        (require database_path('migrations/2026_10_09_000001_add_general_services_to_pharmacy_service_items.php'))->up();
        Department::create(['name' => 'Pharmacy']);
        Department::create(['name' => 'Laboratory']);
        foreach (['pharmacist', 'pharmacy_technician', 'head_of_pharmacy', 'head_of_department', 'administrator'] as $name) Role::withoutEvents(fn () => Role::create(['name' => $name]));
        PaymentMethod::create(['name' => 'Cash', 'is_active' => true]);
        Medicine::create(['name' => 'Test medicine']);
        MedicineBatch::create(['medicine_id' => 1, 'batch_number' => 'B1', 'expiry_date' => today()->addYear(), 'quantity_remaining' => 10, 'selling_price' => 120]);
        PharmacyService::create(['name' => 'Wound dressing', 'price' => 500, 'is_active' => true]);
        $this->actingAs($this->user('pharmacy_technician'));
    }

    private function user(string $role, int $department = 1): User
    {
        return User::withoutEvents(function () use ($role, $department) {
            $user = User::create(['name' => 'Staff', 'email' => uniqid().'@example.test', 'password' => 'password', 'department_id' => $department]);
            $user->assignRole($role);
            return $user;
        });
    }

    private function serviceItem(): array { return ['service_id' => 1, 'quantity' => 1, 'price' => 500, 'subtotal' => 1]; }
    private function medicineItem(): array { return ['batch_id' => 1, 'quantity' => 2, 'price' => 1, 'subtotal' => 1]; }
    private function checkout(array $items, string $name = 'Test Patient'): StockTransaction
    {
        return app(PharmacyCheckout::class)->complete($items, ['paymentMethodId' => 1, 'referenceNumber' => ''], $name);
    }

    public function test_mixed_checkout_creates_one_bill_payment_and_receipt_using_authoritative_prices(): void
    {
        $tx = $this->checkout([$this->medicineItem(), $this->serviceItem()]);
        $this->assertSame(740.0, (float) $tx->total_amount);
        $this->assertSame(8, MedicineBatch::find(1)->quantity_remaining);
        $this->assertDatabaseCount('bills', 1); $this->assertDatabaseCount('payments', 1);
        $this->assertSame('paid', $tx->bill->status);
        $this->assertSame('Test Patient', $tx->bill->patientName());
        $this->assertSame(0.0, (float) $tx->bill->balance);
        $this->assertTrue($tx->bill->isAmountConsistent());
        PharmacyService::find(1)->update(['name' => 'Changed name', 'price' => 900]);
        $html = view('pharmacy.partials.combined-receipt', ['transaction' => $tx->fresh(), 'payment' => $tx->payment])->render();
        foreach (['Test medicine', 'Wound dressing', 'Test Patient', '740.00', '500.00', 'Thank you.'] as $text) $this->assertStringContainsString($text, $html);
        $this->assertStringNotContainsString('Changed name', $html);
    }

    public function test_service_only_checkout_does_not_move_stock(): void
    {
        $tx = $this->checkout([$this->serviceItem()]);
        $this->assertSame(500.0, (float) $tx->payment->amount);
        $this->assertDatabaseCount('stock_transaction_items', 0);
        $this->assertDatabaseCount('pharmacy_dispenses', 0);
        $this->assertSame(10, MedicineBatch::find(1)->quantity_remaining);
    }

    public function test_inactive_service_rolls_back_previous_stock_deduction(): void
    {
        PharmacyService::find(1)->update(['is_active' => false]);
        try { $this->checkout([$this->medicineItem(), $this->serviceItem()]); $this->fail('Expected validation failure'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('cart', $e->errors()); }
        foreach (['stock_transactions', 'stock_transaction_items', 'pharmacy_dispenses', 'pharmacy_service_items', 'bills', 'payments'] as $table) $this->assertDatabaseCount($table, 0);
        $this->assertSame(10, MedicineBatch::find(1)->quantity_remaining);
    }

    public function test_changed_price_requires_review_before_payment(): void
    {
        PharmacyService::find(1)->update(['price' => 600]);
        $this->expectException(ValidationException::class);
        $this->checkout([$this->serviceItem()]);
    }

    public function test_patient_name_required_for_services(): void
    {
        $this->expectException(ValidationException::class);
        $this->checkout([$this->serviceItem()], '  ');
    }

    public function test_fractional_quantity_cannot_be_silently_truncated(): void
    {
        $this->expectException(ValidationException::class);
        $this->checkout([array_replace($this->serviceItem(), ['quantity' => 1.5])]);
    }

    public function test_expired_stock_rolls_back_service_charge(): void
    {
        MedicineBatch::find(1)->update(['expiry_date' => today()->subDay()]);
        try { $this->checkout([$this->serviceItem(), $this->medicineItem()]); $this->fail('Expected validation failure'); }
        catch (ValidationException $e) { $this->assertArrayHasKey('cart', $e->errors()); }
        $this->assertDatabaseCount('pharmacy_service_items', 0); $this->assertDatabaseCount('payments', 0);
    }

    public function test_expiry_badge_variants_follow_remaining_time_bands(): void
    {
        $cases = [
            [today()->subDay(), 'danger'],
            [today()->addMonths(4), 'danger'],
            [today()->addMonths(5), 'warning'],
            [today()->addMonths(11), 'warning'],
            [today()->addYear(), 'success'],
            [today()->addYears(2), 'success'],
            [today()->addYears(2)->addDay(), 'primary'],
        ];

        foreach ($cases as [$expiryDate, $variant]) {
            $batch = new MedicineBatch(['expiry_date' => $expiryDate->toDateString()]);
            $this->assertSame($variant, $batch->expiryBadgeVariant());
        }
    }

    public function test_disabled_payment_method_is_rejected(): void
    {
        PaymentMethod::find(1)->update(['is_active' => false]);
        $this->expectException(ValidationException::class);
        $this->checkout([$this->serviceItem()]);
    }

    public function test_only_pharmacy_head_can_set_and_deactivate_charges(): void
    {
        foreach ([$this->user('pharmacist'), $this->user('pharmacy_technician'), $this->user('head_of_department', 2), $this->user('head_of_pharmacy', 2)] as $user) {
            Livewire::actingAs($user)->test(ServiceManager::class)->assertForbidden();
        }
        Livewire::actingAs($this->user('head_of_pharmacy'))->test(ServiceManager::class)
            ->set('name', 'Consultation')->set('price', '250.50')->call('save')->assertHasNoErrors()
            ->call('edit', 1)->set('isActive', false)->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('pharmacy_services', ['name' => 'Consultation', 'price' => 250.50]);
        $this->assertFalse(PharmacyService::find(1)->is_active);
    }

    public function test_medicine_only_checkout_still_allows_anonymous_walk_in(): void
    {
        $tx = $this->checkout([$this->medicineItem()], '');
        $this->assertSame(240.0, (float) $tx->payment->amount);
        $this->assertNull($tx->patient_name);
        $this->assertDatabaseCount('pharmacy_service_items', 0);
    }

    public function test_disabled_installation_blocks_checkout(): void
    {
        $this->mock(\App\Services\LicenseService::class, fn ($mock) => $mock->shouldReceive('moduleEnabled')->andReturn(false));
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->checkout([$this->serviceItem()]);
    }

    public function test_technician_receipt_route_and_service_setup_are_protected(): void
    {
        $this->mock(\App\Services\SidebarService::class, function ($mock) {
            $mock->shouldReceive('groupsFor')->andReturn([]);
            $mock->shouldReceive('canShowActivities')->andReturn(false);
        });
        $tx = $this->checkout([$this->serviceItem()]);
        $this->get(route('pharmacy.receipts.show', $tx->payment))->assertOk()->assertSee('Wound dressing');
        $this->get(route('pharmacy.services.index'))->assertForbidden();
        $this->actingAs($this->user('administrator'));
        $this->get(route('pharmacy.receipts.show', $tx->payment))->assertForbidden();
    }

    public function test_technician_can_complete_combined_cart_from_transaction_screen(): void
    {
        Livewire::test(TransactionWorkspace::class)->call('addBatchToCart', 1)->call('addServiceToCart', 1)
            ->set('patientName', 'Walk-in Patient')->call('completeTransaction')->assertHasNoErrors()
            ->assertSet('cart', [])->assertDispatched('print-pharmacy-thermal');
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame(620.0, (float) Payment::first()->amount);
    }

    public function test_general_catalog_services_appear_and_can_be_charged_in_pharmacy_transactions(): void
    {
        $service = Service::create(['code' => 'GEN-PHARM-1', 'name' => 'General pharmacy service', 'price' => 350, 'category' => 'General', 'is_active' => true]);

        Livewire::test(TransactionWorkspace::class)
            ->assertSee('General pharmacy service')
            ->call('addCatalogServiceToCart', $service->id)
            ->assertSet('cart.0.catalog_service_id', $service->id)
            ->set('patientName', 'Walk-in Customer')
            ->call('completeTransaction')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pharmacy_service_items', [
            'service_id' => $service->id,
            'pharmacy_service_id' => null,
            'name' => $service->name,
            'price' => 350,
        ]);
        $this->assertSame(350.0, (float) Payment::first()->amount);
    }
}
