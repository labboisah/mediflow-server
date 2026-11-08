<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PartnerOnboardingSubmission extends Model {
 protected $table='partner_onboarding_submissions';
 protected $guarded=[];
 protected $casts=['details'=>'array','requirements'=>'array'];
}
