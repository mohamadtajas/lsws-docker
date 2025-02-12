<?php

namespace App\Http\Requests\PickupPoint;

use App\Http\Requests\GeneralRequest;

class UpdateRequest extends GeneralRequest
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
            'address' => 'required|string',
            'phone' => 'required|string|max:255|regex:/^\+?[0-9]{10,15}$/',
            'pick_up_status' => 'nullable|integer|in:1,0',
            'staff_id' => 'required|integer|exists:staff,id',
            'lang' => 'required|string|exists:languages,code',
        ];
    }
}
