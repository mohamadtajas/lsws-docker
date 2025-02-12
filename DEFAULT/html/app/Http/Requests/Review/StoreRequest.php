<?php

namespace App\Http\Requests\Review;

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
        if ($this->is('api/*')) {
            return [
                'product_id' => 'required|integer|exists:products,id',
                'rating' => 'required|numeric|between:0,5',
                'comment' => 'required|string',
            ];
        }
        return [
            'order_id' => 'required|exists:order_details,id',
            'rating' => 'required|numeric|between:0,5',
            'comment' => 'required|string',
            'photos' => 'required|array',
        ];
    }
}
