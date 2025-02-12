<?php

namespace App\Http\Requests\Address;

use App\Http\Requests\GeneralRequest;

class AddressRequest extends GeneralRequest
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
            'id' => 'nullable|integer|exists:addresses,id',
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'id_number' => ['required', 'numeric', 'digits_between:11,20'],
            'address' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'integer', 'exists:countries,id,status,1'],
            'state_id' => ['required', 'integer', 'exists:states,id,status,1'],
            'city_id' => ['required', 'integer', 'exists:cities,id,status,1'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255', 'regex:/^\+?[0-9]{10,15}$/'],
        ];
    }
}
