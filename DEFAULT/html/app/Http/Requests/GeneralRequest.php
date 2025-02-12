<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GeneralRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            //
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson() || $this->is('api/*')) {
            $response = response()->json([
                'result' => false,
                'message' =>  $validator->errors()
            ]);

            throw new HttpResponseException($response);
        }

        // Flash individual error messages if needed
        foreach ($validator->errors()->all() as $error) {
            flash($error)->error();
        }

        // Throw the validation exception
        throw new HttpResponseException(
            redirect()->back()->withInput()->withErrors($validator)
        );
    }
    protected function prepareForValidation()
    {
        // Get all rule keys, including array keys
        $rules = array_keys($this->rules());

        // Replace the request input with only the fields that match the rule keys
        $filteredInput = $this->filterInput($this->all(), $rules);

        if ($this->has('button')) {
            $filteredInput['button'] = $this->input('button');
        }

        $this->replace($filteredInput);
    }

    /**
     * Recursively filter input based on the provided rules.
     *
     * @param array $input
     * @param array $rules
     * @return array
     */
    protected function filterInput(array $input, array $rules)
    {
        $filtered = [];

        foreach ($rules as $rule) {
            if (strpos($rule, '.') !== false) {
                // Handle nested rules
                $keys = explode('.', $rule);
                $value = $input;

                foreach ($keys as $key) {
                    if (!isset($value[$key])) {
                        $value = null;
                        break;
                    }
                    $value = $value[$key];
                }

                if ($value !== null) {
                    $filtered = array_merge_recursive($filtered, $this->buildNestedArray($keys, $value));
                }
            } else {
                // Handle top-level rules
                if (isset($input[$rule])) {
                    $filtered[$rule] = $input[$rule];
                }
            }
        }

        return $filtered;
    }

    /**
     * Build a nested array from an array of keys and a value.
     *
     * @param array $keys
     * @param mixed $value
     * @return array
     */
    protected function buildNestedArray(array $keys, $value)
    {
        if (count($keys) === 1) {
            return [$keys[0] => $value];
        }

        $key = array_shift($keys);
        return [$key => $this->buildNestedArray($keys, $value)];
    }
    public function attributes()
    {
        // Get all the field names (rule keys) and return them as they are
        return collect($this->rules())->keys()->mapWithKeys(function ($key) {
            $formattedKey = ucfirst(strtolower(str_replace('_', ' ', $key)));
            return [$key => $formattedKey];
        })->toArray();
    }
}
