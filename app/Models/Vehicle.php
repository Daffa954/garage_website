<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = ["brand", 'license_plate', 'total_kwh','owner_id', 'category'];
    //
    // Eloquent ORM
   
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
