<?php

namespace App\Models;

use App;
use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;

class Product extends Model
{
    protected $guarded = ['choice_attributes'];

    protected $with = ['product_translations', 'taxes', 'thumbnail'];

    private static $exchangeRates = null;

    private function loadExchangeRates()
    {
        if (self::$exchangeRates === null) {
            self::$exchangeRates = Currency::where('status' , 1)->pluck('exchange_rate', 'code')->toArray();
            self::$exchangeRates['default'] = Currency::find(get_setting('system_default_currency'))->exchange_rate;
        }
        return self::$exchangeRates;
    }

    private function convert_to_default_currency($price, $source_currency_code)
    {
        $exchangeRates = $this->loadExchangeRates();

        $sourceRate = $exchangeRates[$source_currency_code] ?? 1;
        $defaultRate = $exchangeRates['default'] ?? 1;

        return ($price / $sourceRate) * $defaultRate;
    }

    public function getUnitPriceAttribute($value)
    {
        return $this->convert_to_default_currency($value, $this->currency);
    }

    public function getPurchasePriceAttribute($value)
    {
        return $this->convert_to_default_currency($value, $this->currency);
    }

    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    public function getTranslation($field = '', $lang = false)
    {
        $lang = $lang ?: App::getLocale();
        $productTranslation = $this->product_translations->where('lang', $lang)->first();
        return $productTranslation ? $productTranslation->$field : $this->$field;
    }

    public function product_translations()
    {
        return $this->hasMany(ProductTranslation::class);
    }

    public function main_category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->where('status', 1);
    }

    public function product_queries()
    {
        return $this->hasMany(ProductQuery::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function taxes()
    {
        return $this->hasMany(ProductTax::class);
    }

    public function flash_deal_product()
    {
        return $this->hasOne(FlashDealProduct::class);
    }

    public function bids()
    {
        return $this->hasMany(AuctionProductBid::class);
    }

    public function thumbnail()
    {
        return $this->belongsTo(Upload::class, 'thumbnail_img');
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function scopePhysical($query)
    {
        return $query->where('digital', 0);
    }

    public function scopeDigital($query)
    {
        return $query->where('digital', 1);
    }

    public function scopeIsApprovedPublished($query)
    {
        return $query->where('approved', '1')->where('published', 1);
    }
}
