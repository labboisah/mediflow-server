<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartnershipAgreement extends Model {
 protected $table='partnership_agreements';
 protected $guarded=[];
 protected $casts=['capabilities'=>'array','service_ids'=>'array','effective_at'=>'datetime','expires_at'=>'datetime','accepted_at'=>'datetime'];
}
