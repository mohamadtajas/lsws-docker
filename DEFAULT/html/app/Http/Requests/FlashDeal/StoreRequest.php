<?php

namespace App\Http\Requests\FlashDeal;

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
            'title' => 'required|string',
            'text_color' => 'nullable|string',
            'background_color' => 'nullable|string',
            'date_range' => 'required|string',
            'banner' => 'required|integer|exists:uploads,id',
            'products' => 'required|array',
            'products.*' => 'required|integer|distinct|exists:products,id',
            'lang' => 'nullable|string|exists:languages,code,status,1',
        ];

        foreach($this->products as $product) {
            $rules['discount_'.$product] = 'required|numeric';
            $rules['discount_type_'.$product] = 'required|string';
        }

        return $rules;
    }
}
