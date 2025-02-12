<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrendyolOrder extends Model
{
    protected $fillable = [
        'order_id' , 'order_detail_id','trendyol_orderNumber', 'trendyol_product_id', 'trendyol_kampanyaID', 'trendyol_listeID', 'trendyol_saticiID', 'trendyol_adet' ,'trendyol_success' 
    ];
    
    public function order(){
        return $this->belongsTo(Order::class);
    }
}
