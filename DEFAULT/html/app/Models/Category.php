<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\PreventDemoModeChanges;
use App\Models\CustomBelongsToMany;
use App;

class Category extends Model
{
    protected $with = ['category_translations'];
    protected $guarded = [];

    public function getTranslation($field = '', $lang = false)
    {
        $lang = $lang == false ? App::getLocale() : $lang;
        $category_translation = $this->category_translations->where('lang', $lang)->first();
        return $category_translation != null ? $category_translation->$field : $this->$field;
    }

    public function category_translations()
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    public function coverImage()
    {
        return $this->belongsTo(Upload::class, 'cover_image');
    }

    public function catIcon()
    {
        return $this->belongsTo(Upload::class, 'icon');
    }

    public function products()
    {
        return new CustomBelongsToMany(
            Product::query(),
            $this,
            'product_categories',
            'category_id',
            'product_id',
            'id',
            'id'
        );
    }

    public function bannerImage()
    {
        return $this->belongsTo(Upload::class, 'banner');
    }

    public function classified_products()
    {
        return $this->hasMany(CustomerProduct::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function childrenCategories()
    {
        return $this->hasMany(Category::class, 'parent_id')->with('categories');
    }

    public function parentCategory()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class);
    }

    public function sizeChart()
    {
        return $this->belongsTo(SizeChart::class, 'id', 'category_id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
