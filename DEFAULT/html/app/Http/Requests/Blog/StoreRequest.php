<?php

namespace App\Http\Requests\Blog;

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
        return [
            'category_id' => [
                'required',
                Rule::exists('blog_categories', 'id')->where(function ($query) {
                    $query->whereNull('deleted_at'); // Ensure deleted_at is null
                }),
            ],
            'title' => 'required|max:255',
            'banner' => 'required|integer|exists:uploads,id',
            'slug' => 'required|string|max:255',
            'short_description' => 'required|max:255',
            'description' => 'required',
            'meta_title' => 'required|max:255',
            'meta_img'  => 'required|integer|exists:uploads,id',
            'meta_description' => 'required|max:255',
            'meta_keywords' => 'required|max:255',
        ];
    }
}
