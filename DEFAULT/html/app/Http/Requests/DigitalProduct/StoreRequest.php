<?php

namespace App\Http\Requests\DigitalProduct;

use App\Http\Requests\GeneralRequest;
use Illuminate\Validation\Rule;

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
        $rules = [
            'added_by' => 'nullable|in:admin,seller',
            'digital' => 'nullable|in:1',
            'name' => 'required|max:255',
            'unit' => 'sometimes|required',
            'min_qty' => 'sometimes|required|numeric',
            'file_name' => 'required|integer|exists:uploads,id',
            'tags' => 'required|array',
            'tags.*' => 'required|string',
            'photos' => 'required|string',
            'thumbnail_img' => 'required|integer|exists:uploads,id',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
            'meta_img' => 'nullable|integer|exists:uploads,id',
            'unit_price' => 'required|numeric',
            'tax_id' => 'nullable|array',
            'tax_id.*' => 'nullable|integer|exists:taxes,id',
            'tax' => 'nullable|array',
            'tax.*' => 'nullable|numeric',
            'tax_type' => 'nullable|array',
            'tax_type.*' => 'nullable|in:amount,percentage',
            'date_range' => 'nullable|string',
            'discount' => 'nullable|numeric',
            'discount_type' => 'nullable|in:amount,percentage,percent',
            'description' => 'nullable|string',
            'lang' => 'nullable|string|exists:languages,code,status,1',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'required|integer|exists:categories,id,digital,1',
            'category_id' => 'nullable|integer|exists:categories,id,digital,1',
            'button' => 'nullable',
            'slug' => 'nullable|string|max:255',
        ];
        if ($this->get('discount_type') == 'amount') {
            $rules['discount'] = 'sometimes|required|numeric|lt:unit_price';
        } else {
            $rules['discount'] = 'sometimes|required|numeric|lt:100';
        }

        return $rules;
    }
}
