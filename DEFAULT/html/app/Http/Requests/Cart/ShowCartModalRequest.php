<?php

namespace App\Http\Requests\Cart;

use App\Http\Requests\GeneralRequest;

class ShowCartModalRequest extends GeneralRequest
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
            'trendyol' => 'nullable|integer|in:1,0',
            'provider' => 'nullable|integer|exists:providers,id',
            'id' => 'required|integer',
            'urunNo' => 'nullable|integer',
        ];
    }
}
