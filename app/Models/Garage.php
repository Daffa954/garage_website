<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Garage extends Model
{
    //
    const MAX_GARAGE_TYPE = 999;
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function rents()
    {
        return $this->hasMany(Rent::class, 'garage_id');
    }

    // Rules

}
