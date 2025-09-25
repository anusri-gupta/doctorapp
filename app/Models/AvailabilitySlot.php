<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class AvailabilitySlot extends Model
{
    protected $fillable = [
        'availability_id',
         'slot_date', 
        'day_of_week',
        'start_time',
        'end_time',
        'max_appointments'    
    ];
    use SoftDeletes;

    public function availability()
    {
        return $this->belongsTo(Availability::class);
    }
}
