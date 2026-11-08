<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DailyReportExport implements FromView, WithTitle, ShouldAutoSize, WithStyles
{
    protected $reportData;

    public function __construct($reportData)
    {
        $this->reportData = $reportData;
    }

    public function view(): View
    {
        return view('reports.excel', [
            'reportData' => $this->reportData
        ]);
    }

    public function title(): string
    {
        return 'Daily Report - ' . \Carbon\Carbon::parse($this->reportData['date'])->format('M d, Y');
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header styling
            1 => ['font' => ['bold' => true, 'size' => 16]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],

            // Section headers
            'A5:Z5' => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '007BFF']]],
        ];
    }
}