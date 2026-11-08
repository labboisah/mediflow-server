<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartnerCaseEvent extends Model {
 protected $table='partner_case_events';
 protected $guarded=[];
 protected $casts=['details'=>'array','occurred_at'=>'datetime'];
}
