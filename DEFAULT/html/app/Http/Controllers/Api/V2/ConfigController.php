<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessSettings\KeysRequest;
use App\Models\Addon;
use App\Models\BusinessSetting;

class ConfigController extends Controller
{
    public function addon_list()
    {
        $addons = Addon::all();

        return response()->json($addons);
    }

    public function activated_social_login()
    {
        $activated_social_login_list = BusinessSetting::whereIn('type', ['facebook_login', 'google_login', 'twitter_login'])->get();
        return response()->json($activated_social_login_list);
    }

    public function business_settings(KeysRequest $request)
    {
        $business_settings = BusinessSetting::whereIn('type', explode(',', $request->keys))->get()->toArray();

        return response()->json($business_settings);
    }
}
