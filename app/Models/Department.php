<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Department extends Model
{
    protected $guarded = [];

    public function services() {
        return $this->hasMany(Service::class);
    }

    public function investigationTypes() {
        return $this->hasMany(InvestigationType::class);
    }

    public function consumables() {
        return $this->hasMany(Consumable::class);
    }

    public function bills() {
        return $this->hasMany(Bill::class);
    }

    public function expenses() {
        return $this->hasMany(Expense::class);
    }


    public function investigationRequests()
    {
        return InvestigationRequest::whereHas(
            'investigation.investigationType',
            fn ($q) => $q->where('department_id', $this->id)
        );
    }

    public function requestsQuery()
    {
        return InvestigationRequest::whereHas(
            'investigation.investigationType',
            fn($q) => $q->where('department_id', $this->id)
        );
    }

    public function requestStats()
    {
        $q = $this->requestsQuery();

        return [

            'today' => (clone $q)
                ->whereDate('created_at', today())
                ->count(),

            'paid' => (clone $q)
                ->whereHas('bill', fn($b) => $b->where('status', 'Paid'))
                ->count(),

            'payment_in_progress' => (clone $q)
                ->whereHas('bill', fn($b) => $b->where('status', 'Partial'))
                ->count(),

            'pending' => (clone $q)
                ->where('status', 'Pending')
                ->count(),

            'completed' => (clone $q)
                ->where('status', 'Completed')
                ->count(),

        ];
    }

    public function revenue()
    {
        $q = $this->requestsQuery()
            ->whereHas('bill', fn($b) => $b->where('status', 'paid'));

        return [

            'today' => (clone $q)
                ->whereDate('created_at', today())
                ->with('bill')
                ->get()
                ->sum(fn($r) => $r->bill->amount),

            'this_month' => (clone $q)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->with('bill')
                ->get()
                ->sum(fn($r) => $r->bill->amount),

            'total' => (clone $q)
                ->with('bill')
                ->get()
                ->sum(fn($r) => $r->bill->amount),

        ];
    }

    public function expensesFrom($start_date, $end_date) {
        return $this->expenses->whereBetween('expense_date',
                [$start_date,$end_date])
            ->sum('amount');
    }

    public function revenuesFrom($start_date, $end_date) {
        $amount = 0;
        foreach($this->bills as $bill){
            if($bill->whereBetween('created_at',[$start_date,$end_date])){
                $amount += $bill->amount;
            }
        }
        return $amount;
    }

    public function consumablesFrom($start_date, $end_date) {
        $amount = 0;
        return ConsumableStock::whereIn(
        'consumable_id',
        $this->consumables->pluck('id'))
        ->whereBetween('purchase_date', [$start_date, $end_date])
        ->sum(DB::raw('quantity * unit_price')); 
    }

}
