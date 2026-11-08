<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartnerType extends Model {
 protected $table='partner_types';
 protected $guarded=[];
 protected $casts=['requirements'=>'array','is_active'=>'boolean'];
}
