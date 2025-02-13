<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Auth\AccessTokenRequest;
use App\Http\Resources\V2\UserCollection;
use App\Models\User;

use Laravel\Sanctum\PersonalAccessToken;


class UserController extends Controller
{
    public function getUserInfoByAccessToken(AccessTokenRequest $request)
    {

        $false_response = [
            'result' => false,
            'id' => 0,
            'name' => "",
            'email' => "",
            'avatar' => "",
            'avatar_original' => "",
            'phone' => ""
        ];

        $token = PersonalAccessToken::findToken($request->access_token);

        if (!$token) {
            return response()->json($false_response);
        }

        $user = $token->tokenable;

        if ($user == null) {
            return response()->json($false_response);
        }

        return response()->json([
            'result' => true,
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'avatar_original' => uploaded_asset($user->avatar_original),
            'phone' => $user->phone
        ]);
    }
}
