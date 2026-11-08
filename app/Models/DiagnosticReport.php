<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosticReport extends Model
{
    protected $guarded = [];

    protected $casts = ['performed_at' => 'datetime', 'reported_at' => 'datetime'];

    public function investigationRequest()
    {
        return $this->belongsTo(InvestigationRequest::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function revisions()
    {
        return $this->hasMany(DiagnosticReportRevision::class)->orderBy('version');
    }
}
