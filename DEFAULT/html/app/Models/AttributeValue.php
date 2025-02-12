<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    protected $fillable= ['attribute_id' , 'value' , 'trendyol_id' , 'uniqueId', 'color_code'] ;
    public function attribute() {
        return $this->belongsTo(Attribute::class);
    }
}
