<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\GeneralRequest;

class TrendyolBulkUploadRequest extends GeneralRequest
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
            'supplier_id' => 'required|string',
            'api_user_name' => 'required|string',
            'api_password' => 'required|string',
            'page' => 'nullable|numeric'
        ];
    }
}
