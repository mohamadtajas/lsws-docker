<?php

namespace App\Http\Requests\BusinessSettings;

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
        $rules = [
            'types' => 'required|array',
        ];

        foreach ($this->input('types') as $key => $type) {
            if (is_array($type)) {
                $rules["types.$key"] = 'nullable|array';
                foreach ($type as $subKey => $value) {
                    if (is_array($this->input($value))) {
                        $rules[$value] = 'nullable|array';
                        foreach ($this->input($value) as $key => $item) {
                            $rules[$item] = 'nullable|string';
                        }
                    } else {
                        $rules[$value] = 'nullable|string';
                    }
                }
            } else {
                $rules["types.$key"] = 'nullable|string';
                if (is_array($this->input($type))) {
                    $rules[$type] = 'nullable|array';
                    foreach ($this->input($type) as $key => $value) {
                        $rules[$value] = 'nullable|string';
                    }
                } else {
                    $rules[$type] = 'nullable|string';
                }
            }
        }

        return $rules;
    }
}
