<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartnerMembership extends Model {
 protected $table='partner_memberships';
 protected $guarded=[];
 protected $casts=['is_active'=>'boolean'];
}
