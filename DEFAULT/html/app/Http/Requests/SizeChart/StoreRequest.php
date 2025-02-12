<?php

namespace App\Http\Requests\SizeChart;

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
            'name' => 'required|string',
            'category_id' => 'required|numeric|unique:size_charts,category_id,' . $this->id,
            'fit_type' => 'nullable|string|in:slim_fit,regular_fit,relaxed',
            'stretch_type' => 'nullable|string|in:non,slight,medium,hign',
            'photos' => 'nullable|string',
            'description' => 'nullable|string',
            'measurement_points' => 'required',
            'size_options' => 'required',
            'measurement_option' => 'required',
            'size_chart_values.*' => 'required',
        ];
    }

    protected function prepareForValidation()
    {
        $measurement_points = json_encode($this->measurement_points);
        $size_options = json_encode($this->size_options);
        $measurement_option = isset($this->measurement_option) ? json_encode($this->measurement_option) :  null;

        $this->merge([
            'measurement_points' => $measurement_points,
            'size_options'       => $size_options,
            'measurement_option' => $measurement_option
        ]);
    }
}
