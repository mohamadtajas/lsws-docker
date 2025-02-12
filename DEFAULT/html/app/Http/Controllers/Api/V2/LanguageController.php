<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\Language;
use App\Http\Resources\V2\LanguageCollection;
use Cache;

class LanguageController extends Controller
{
    public function getList()
    {
        return new LanguageCollection(Language::where('status', 1)->get());
    }
}
