<?php

namespace App\Http\Requests\Upload;

use App\Http\Requests\GeneralRequest;

class AizUploadRequest extends GeneralRequest
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
            'aiz_file' => 'required|file|mimes:jpg,jpeg,png,svg,webp,gif,mp4,mpg,mpeg,webm,ogg,avi,mov,flv,swf,mkv,wmv,wma,aac,wav,mp3,zip,rar,7z,doc,txt,docx,pdf,csv,xml,ods,xlr,xls,xlsx|max:20480', // File is required and must be one of the specified types with a max size of 20MB (adjust if necessary)
        ];
    }
}
