<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhotoAlbum extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'file_path'];

    public function getPhotoUrlAttribute()
    {
        return \Storage::disk('product_photos')->url($this->file_path); // Generate URL using the 'product_photos' disk
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}