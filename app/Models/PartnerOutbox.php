<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartnerOutbox extends Model {
 protected $table='partner_outbox';
 protected $guarded=[];
 protected $casts=['next_attempt_at'=>'datetime','delivered_at'=>'datetime'];
}
