<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [ 'price', 'stock', 'room_name', 'room_capacity'];

    // Define the relationship to bookings
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
