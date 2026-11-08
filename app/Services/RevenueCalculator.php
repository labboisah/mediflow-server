<?php
namespace App\Services;

class RevenueCalculator
{
    public static function calculate($service, $amount)
    {
        $rule = $service->category->revenueRule;

        return [

            'radiologist' => $amount * ($rule->radiologist_percent / 100),

            'radiographer' => $amount * ($rule->radiographer_percent / 100),

            'staff' => $amount * ($rule->staff_percent / 100),

            'annex' => $amount * ($rule->annex_percent / 100),

            'amount' => $amount

        ];
    }
}