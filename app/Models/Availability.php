<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Availability extends Model
{
    protected $fillable = [
        'doctor_id',
        'start_date',
        'end_date',
        'slot_duration','monday', 'tuesday', 'wednesday', 'thursday',
    'friday', 'saturday', 'sunday'
    ];
    use SoftDeletes;

        public function doctor()
        {
            return $this->belongsTo(Doctor::class);
        }
        public function slots()
        {
            return $this->hasMany(AvailabilitySlot::class);
        }
}
