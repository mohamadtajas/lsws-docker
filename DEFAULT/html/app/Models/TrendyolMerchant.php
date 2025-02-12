<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrendyolMerchant extends Model
{
    protected $fillable = [
        'trendyol_id',
        'name',
        'official_name',
        'email',
        'tax_number'
    ];

    public function order()
    {
        return $this->hasMany(OrderDetail::class);
    }
}
