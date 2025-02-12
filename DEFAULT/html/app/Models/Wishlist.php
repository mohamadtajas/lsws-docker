<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $guarded = [];
    protected $fillable = ['user_id','product_id' , 'urunNo'  , 'trendyol' , 'provider_id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
