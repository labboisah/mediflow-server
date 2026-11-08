<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartnerDisclosure extends Model {
 protected $table='partner_disclosures';
 protected $guarded=[];
 protected $casts=['packet'=>'array','expires_at'=>'datetime','revoked_at'=>'datetime'];
}
