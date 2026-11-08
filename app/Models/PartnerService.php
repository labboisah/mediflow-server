<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartnerService extends Model {
 protected $table='partner_services';
 protected $guarded=[];
 protected $casts=['is_active'=>'boolean'];
}
