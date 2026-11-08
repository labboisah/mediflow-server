<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartnerVerification extends Model {
 protected $table='partner_verifications';
 protected $guarded=[];
 protected $casts=['checks'=>'array'];
}
