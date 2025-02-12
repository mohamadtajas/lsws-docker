<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\ResourceCollection;

class WishlistCollection extends ResourceCollection
{
    protected $additionalData;

    /**
     * Constructor to initialize the collection and additional data.
     *
     * @param mixed $resource
     * @param array $additionalData
     */
    public function __construct($resource, array $additionalData = [])
    {
        parent::__construct($resource);
        $this->additionalData = $additionalData;
    }

    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($data) {
                $product = $this->getProductDetails($data);

                return [
                    'id' => (int) $data->id,
                    'product' => $product,
                ];
            }),
        ];
    }

    /**
     * Retrieve product details based on different conditions.
     *
     * @param $data
     * @return array
     */
    protected function getProductDetails($data)
    {
        if ($data->trendyol == 0 && $data->provider_id === null) {
            return [
                'id' => $data->product->id,
                'urunNo' => '',
                'name' => $data->product->name,
                'thumbnail_image' => uploaded_asset($data->product->thumbnail_img),
                'base_price' => format_price(home_base_price($data->product, false)),
                'rating' => (float) $data->product->rating,
                'trendyol' => $data->trendyol,
                'provider_id' => $data->provider_id,
            ];
        }

        if ($data->trendyol == 1) {
            $accessToken = trendyol_account_login();
            $product = trendyol_product_details($accessToken, $data->product_id, $data->urunNo);

            if ($product) {
                return [
                    'id' => $product['id'],
                    'urunNo' => $product['urunNo'],
                    'name' => $product['name'],
                    'thumbnail_image' => $product['photos'][0] ?? null,
                    'base_price' => single_price(str_replace(',', '', $product['new_price'])),
                    'rating' => $product['rating'],
                    'trendyol' => $data->trendyol,
                    'provider_id' => $data->provider_id,
                ];
            }
        }

        if ($data->provider_id !== null && isset($this->additionalData[$data->id])) {
            $product = $this->additionalData[$data->id];

            return [
                'id' => $product['id'],
                'urunNo' => '',
                'name' => $product['name'],
                'thumbnail_image' => $product['thumbnail'] ?? null,
                'base_price' => single_price(str_replace(',', '', $product['new_price'])),
                'rating' => $product['rating'],
                'trendyol' => $data->trendyol,
                'provider_id' => $data->provider_id,
            ];
        }

        return [];
    }

    /**
     * Additional meta data to include with the resource response.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function with($request)
    {
        return [
            'success' => true,
            'status' => 200,
        ];
    }
}
