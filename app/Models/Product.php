<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_name',
        'room_capacity',
        'price',
        'stock',
        'description',
    ];

    public function photos()
    {
        return $this->hasMany(PhotoAlbum::class);
    }
}