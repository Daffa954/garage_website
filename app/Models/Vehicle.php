<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    //
    // Eloquent ORM
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function owner(){
        return $this->belongsTo(User::class);
    }
}
