<?php

namespace App\Exports;

use App\Models\Bill;
use App\Models\Expense;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Exports\Sheets\Summary;
use App\Exports\Sheets\DetailedBills;

class FinancialReportExport implements WithMultipleSheets
{
    use Exportable;

    protected $from;
    protected $to;
    protected $reportId;
    protected $isAdmin;

    public function __construct($from, $to, $reportId, $isAdmin = false)
    {
        $this->from = $from;
        $this->to = $to;
        $this->reportId = $reportId;
        $this->isAdmin = $isAdmin;
    }

    public function sheets(): array
    {
        return [
            new Summary($this->from, $this->to, $this->reportId, $this->isAdmin),
            new DetailedBills($this->from, $this->to, $this->isAdmin),
        ];
    }
}