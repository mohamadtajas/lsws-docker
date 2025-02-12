<?php

namespace App\Http\Requests\CustomerProduct;

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
        return [
            'name' => 'required|string',
            'added_by' => 'nullable|string|in:admin,seller,customer',
            'category_id' => 'required|integer|exists:categories,id',
            'brand_id' => 'required|integer|exists:brands,id',
            'conditon' => 'required|string|in:new,used',
            'location' => 'required|string',
            'photos' => 'required|string',
            'thumbnail_img' => 'required|integer|exists:uploads,id',
            'unit' => 'required|string',
            'tags' => 'required|array',
            'tags.*' => 'required|string',
            'description' => 'nullable|string',
            'video_provider' => 'required|string|in:youtube,vimeo,dailymotion',
            'video_link' => 'nullable|string',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
            'meta_img' => 'nullable|integer|exists:uploads,id',
            'unit_price' => 'required|numeric',
            'pdf' => 'nullable|integer|exists:uploads,id,extension,pdf',
            'lang' => 'nullable|string|exists:languages,code,status,1',
            'slug' => 'nullable|string|max:255',
        ];
    }
}
