<?php

namespace App\Http\Requests\Conversation;

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
            'product_id' => 'required|integer|exists:products,id',
            'title' => 'required|string|max:1000',
            'message' => 'required|string',
        ];
    }
}
