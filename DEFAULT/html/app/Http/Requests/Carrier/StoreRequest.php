<?php

namespace App\Http\Requests\Carrier;

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
        $rules = [
            'carrier_name'      => 'required|string|max:255',
            'transit_time'      => 'required|string|max:255',
            'logo'              => 'nullable|integer|exists:uploads,id',
            'shipping_type'     => 'nullable|string|in:on',
        ];

        if (!$this->has('shipping_type')) {
            $rules = array_merge($rules, [
                'billing_type'      => 'required|string|in:weight_based,price_based',
                'delimiter1'        => 'required|array',
                'delimiter1.*'      => 'required|numeric|min:0',
                'delimiter2'        => 'required|array',
                'delimiter2.*'      => 'required|numeric|gt:delimiter1.*',
                'zones'             => 'required|array',
                'zones.*'           => 'required|integer|exists:zones,id'
            ]);
            foreach ($this->input('zones', []) as $zone) {
                $rules['carrier_price.' . $zone] = 'required|array';
                $rules['carrier_price.' . $zone . '.*'] = 'required|numeric|min:0';
            }
        }

        return $rules;
    }
}
