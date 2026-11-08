<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosticReportRevision extends Model
{
    protected $guarded = [];
    protected $casts = ['changed_at' => 'datetime'];

    public function report()
    {
        return $this->belongsTo(DiagnosticReport::class, 'diagnostic_report_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
