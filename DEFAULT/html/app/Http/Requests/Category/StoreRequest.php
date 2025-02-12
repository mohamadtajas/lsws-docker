<?php

namespace App\Http\Requests\Category;

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
            'name' => 'required|string|max:255',
            'digital' => 'required|in:0,1',
            'order_level' => 'required|integer',
            'banner' => 'nullable|integer|exists:uploads,id',
            'icon' => 'nullable|integer|exists:uploads,id',
            'cover_image' => 'nullable|integer|exists:uploads,id',
            'meta_title' => 'required|string|max:255',
            'meta_description' => 'required|string|max:500',
            'slug' => 'nullable|string|max:255',
            'commision_rate' => 'nullable|numeric',
            'filtering_attributes' => 'nullable|array',
            'filtering_attributes.*' => 'integer|exists:attributes,id',
            'lang' => [
                'nullable',
                'string',
                Rule::exists('languages', 'code')->where(function ($query) {
                    return $query->where('status', 1); // Only allow languages with status = 1
                }),
            ]
        ];

        if ($this->input('parent_id') != 0) {
            $rules['parent_id'] = 'required|integer|exists:categories,id';
        }

        return $rules;
    }
}
