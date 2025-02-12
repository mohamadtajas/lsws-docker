<?php

namespace App\Http\Requests\SizeChart;

use App\Http\Requests\GeneralRequest;

class GetCombinationRequest extends GeneralRequest
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
            'measurement_option_inch' => 'nullable|numeric',
            'measurement_option_cen' => 'nullable|numeric',
            'measurement_points' => 'required|array',
            'measurement_points.*' => 'required|numeric|exists:measurement_points,id',
            'size_options' => 'required|array',
            'size_options.*' => 'required|numeric|exists:attribute_values,id',
        ];
    }
}
