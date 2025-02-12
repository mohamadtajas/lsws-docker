<?php

namespace App\Http\Resources\V2;

use App\Models\Brand;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SliderCollection extends ResourceCollection
{
    public $collection;
    public $bannerLinks;

    public function __construct($resource, $bannerLinks = null)
    {
        parent::__construct($resource);

        $this->resource = $this->collectResource($resource);
        $this->bannerLinks = $bannerLinks;
    }

    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($data, $key) {
                if ($this->bannerLinks != null) {
                    $url = json_decode(get_setting($this->bannerLinks), true)[$key] ?? '';
                    preg_match('/\/brand\/([^\/]+)/', $url, $matches);
                    $keyword = isset($matches[1]) ? urldecode($matches[1]) : null;
                    $brand =Brand::where('slug', $keyword)->first();
                    return [
                        'photo' => uploaded_asset($data),
                        'keyword' => ( $brand != null ) ? $brand->id : $url
                    ];
                } else {
                    return [
                        'photo' => uploaded_asset($data)
                    ];
                }
            })
        ];
    }

    public function with($request)
    {
        return [
            'success' => true,
            'status' => 200
        ];
    }
}
