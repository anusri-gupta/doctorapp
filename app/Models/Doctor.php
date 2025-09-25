<?php

namespace App\Models;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Doctor extends Authenticatable
{
  
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password'];
    use HasApiTokens;
    public function availabilities()
    {
        return $this->hasMany(Availability::class);
    }
}