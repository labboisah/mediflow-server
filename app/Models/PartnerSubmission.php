<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartnerSubmission extends Model {
 protected $table='partner_submissions';
 protected $guarded=[];
 protected $casts=['reviewed_at'=>'datetime'];
}
