<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\V2\BusinessSettingCollection;
use App\Models\BusinessSetting;

class BusinessSettingController extends Controller
{
    public function index()
    {
        return new BusinessSettingCollection(BusinessSetting::all());
    }

    public function appVersionInfo(){
        $appVersionInfo = [
            'isForceUpdate' => get_setting('app_force_update') == 1 ? true : false,
            'latestVersion' => get_setting('app_version')
        ];
        return response()->json(['result' => true, 'message' => $appVersionInfo], 200);
    }
}
