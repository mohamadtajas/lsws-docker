<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function pickup_point()
    {
        return $this->belongsTo(PickupPoint::class);
    }

    public function refund_request()
    {
        return $this->hasOne(RefundRequest::class);
    }

    public function affiliate_log()
    {
        return $this->hasMany(AffiliateLog::class);
    }

    public function trendyolMerchant()
    {
        return $this->belongsTo(TrendyolMerchant::class, 'trendyol_merchant_id');
    }

    public function trendyol_order()
    {
        return $this->hasOne(TrendyolOrder::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class);
    }
}
