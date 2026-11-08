<?php

namespace App\Models;

use App\Models\Traits\Syncable;
use Illuminate\Database\Eloquent\Model;

class FluidBalance extends Model
{
    use Syncable;
    
    protected $guarded = [];

    public function admission() {
        return $this->belongsTo(Admission::class);
    }
    
    public function recordedBy() {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function totalIn(){
        return    $this->type_in + $this->tupe_in + $this->oral + $this->iv;
    }

    public function totalOut(){
        return    $this->type_out + $this->tupe_out + $this->urine + $this->faces;
    }

    public function balance(){
        return    $this->totalIn() - $this->totalOut();
    }
}
