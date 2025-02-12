<?php

namespace App\Http\Requests\Upload;

use App\Http\Requests\GeneralRequest;

class GetPreviewFilesRequest extends GeneralRequest
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
            'ids' => 'required|string',
            'ids.*' => 'integer|exists:uploads,id',
        ];
    }
}
