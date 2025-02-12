<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\GeneralRequest;

class StoreRequest extends GeneralRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $rules =  [
            'name' => 'required|string',
            'brand_id' => 'nullable|exists:brands,id',
            'unit' => 'required|string',
            'weight' => 'nullable|numeric',
            'min_qty' => 'nullable|numeric|min:1',
            'tags' => 'required|array',
            'tags.*' => 'required|string',
            'barcode' => 'nullable|string',
            'refundable' => 'nullable|in:1,0',
            'category_ids' => 'required|array',
            'category_ids.*' => 'required|integer|exists:categories,id',
            'category_id' => 'required|integer|exists:categories,id',
            'description' => 'nullable|string',
            'featured' => 'nullable|in:0,1',
            'todays_deal' => 'nullable|in:0,1',
            'flash_deal_id' => 'nullable|integer|exists:flash_deals,id',
            'flash_discount_type' => 'nullable|string|in:amount,percent',
            'unit_price' => 'required|numeric|min:0',
            'currency' => 'required|string|exists:currencies,code,status,1',
            'tax_id' => 'nullable|array',
            'tax_id.*' => 'nullable|integer|exists:taxes,id',
            'tax' => 'nullable|array',
            'tax.*' => 'nullable|numeric|min:0',
            'tax_type' => 'nullable|array',
            'tax_type.*' => 'nullable|in:amount,percent',
            'photos' => 'nullable|string',
            'thumbnail_img' => 'nullable|integer|exists:uploads,id',
            'video_provider' => 'nullable|string|in:youtube,vimeo,dailymotion',
            'video_link' => 'nullable|string|url',
            'pdf' => 'nullable|integer|exists:uploads,id,extension,pdf',
            'colors_active' => 'nullable|in:0,1',
            'colors' => 'nullable|array',
            'colors.*' => 'nullable|string|exists:colors,code',
            'choice_attributes' => 'nullable|array',
            'choice_attributes.*' => 'required|exists:attributes,id',
            'date_range' => 'nullable|string',
            'discount_type' => 'required|string|in:amount,percent',
            'earn_point' => 'nullable|numeric|min:0',
            'current_stock' => 'required|numeric|min:0',
            'sku' => 'nullable|string',
            'external_link' => 'nullable|url',
            'external_link_btn' => 'nullable|string',
            'choice_names' => 'nullable|array',
            'choice_names.*' => 'nullable|string',
            'low_stock_quantity' => 'nullable|numeric|min:1',
            'stock_visibility_state' => 'nullable|string|in:quantity,text,hide',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_img' => 'nullable|integer|exists:uploads,id',
            'cash_on_delivery' => 'nullable|in:0,1',
            'shipping_type' => 'nullable|string|in:free,flat_rate',
            'flat_shipping_cost' => 'nullable|numeric|min:0',
            'is_quantity_multiplied' => 'nullable|in:0,1',
            'est_shipping_days' => 'nullable|numeric|min:1',
            'choice_no' => 'nullable|array',
            'choice_no.*' => 'nullable|integer',
            'added_by' => 'nullable|string|in:admin,seller',
        ];

        if ($this->get('flash_discount_type') == 'amount') {
            $rules['flash_discount'] = 'sometimes|required|numeric|lt:unit_price';
        } else {
            $rules['flash_discount'] = 'sometimes|required|numeric|lt:100';
        }

        if ($this->get('discount_type') == 'amount') {
            $rules['discount'] = 'sometimes|required|numeric|lt:unit_price';
        } else {
            $rules['discount'] = 'sometimes|required|numeric|lt:100';
        }

        if ($this->input('choice_attributes')) {
            foreach ($this->input('choice_attributes') as $no) {
                $rules['choice_options_' . $no] = 'nullable|array';
                $rules['choice_options_' . $no . '.*'] = 'nullable|string|exists:attribute_values,value,attribute_id,' . $no;
            }
        }

        if ($this->input('choice_names')) {
            foreach ($this->input('choice_names') as $str) {
                $rules['price_' . $str] = 'required|numeric|min:0';
                $rules['sku_' . $str] = 'nullable|string';
                $rules['qty_' . $str] = 'required|numeric|min:0';
                $rules['img_' . $str] = 'nullable|integer|exists:uploads,id';
            }
        }

        return $rules;
    }
}
