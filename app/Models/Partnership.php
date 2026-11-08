<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Partnership extends Model {
 protected $table='partnerships';
 protected $guarded=[];
 protected $casts=['approved_at'=>'datetime'];
}
