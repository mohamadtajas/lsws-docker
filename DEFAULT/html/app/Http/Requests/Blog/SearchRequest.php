<?php

namespace App\Http\Requests\Blog;

use App\Http\Requests\GeneralRequest;

class SearchRequest extends GeneralRequest
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
            'search' => 'nullable|string',
            'selected_categories' => 'nullable|array',
            'selected_categories.*' => 'exists:blog_categories,slug'
        ];
    }
}
