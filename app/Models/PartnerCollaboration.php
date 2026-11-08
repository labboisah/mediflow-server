<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartnerCollaboration extends Model {
 protected $table='partner_collaborations';
 protected $guarded=[];
 protected $casts=['accepted_at'=>'datetime','completed_at'=>'datetime'];
}
