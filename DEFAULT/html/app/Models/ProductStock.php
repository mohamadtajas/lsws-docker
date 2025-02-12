<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    protected $fillable = ['product_id', 'variant', 'sku', 'price', 'qty', 'image'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function wholesalePrices()
    {
        return $this->hasMany(WholesalePrice::class);
    }

    public function getPriceAttribute($value)
    {
        static $exchangeRates = null;

        if (!$exchangeRates) {
            $exchangeRates = $this->loadExchangeRates();
        }

        $currency = $this->product ? $this->product->currency : null;
        return $currency && isset($exchangeRates[$currency])
            ? $this->convertToDefaultCurrency($value, $currency, $exchangeRates)
            : $value;
    }

    private function convertToDefaultCurrency($price, $sourceCurrencyCode, $exchangeRates)
    {
        $sourceRate = $exchangeRates[$sourceCurrencyCode] ?? 1;
        $defaultRate = $exchangeRates['default'] ?? 1;
        return ($price / $sourceRate) * $defaultRate;
    }

    private function loadExchangeRates()
    {
        $currencies = Currency::whereIn('code', array_merge(['USD'], $this->getAllCurrencyCodes()))->where('status' , 1)
            ->pluck('exchange_rate', 'code')
            ->toArray();

        $currencies['default'] = Currency::find(get_setting('system_default_currency'))->exchange_rate;

        return $currencies;
    }

    private function getAllCurrencyCodes()
    {
        return Product::pluck('currency')->unique()->toArray();
    }

    public function newQuery()
    {
        return parent::newQuery()->with('product');
    }
}
