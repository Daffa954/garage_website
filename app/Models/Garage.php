<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Garage extends Model
{
    //
    const MAX_GARAGE_TYPE = 999;
    protected $fillable = ["garage_code", "name", "price_kwh", "price", "category", "status"];


    public function rents()
    {
        return $this->hasMany(Rent::class, 'garage_id');
    }

    public function activeRent()
{
    return $this->hasOne(Rent::class, 'garage_id')
        ->whereDate('start_date', '<=', today())
        ->whereDate('end_date', '>=', today());
}

    // Rules

}
