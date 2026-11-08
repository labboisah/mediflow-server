<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartnerInvitation extends Model {
 protected $table='partner_invitations';
 protected $guarded=[];
 protected $casts=['expires_at'=>'datetime','accepted_at'=>'datetime'];
}
