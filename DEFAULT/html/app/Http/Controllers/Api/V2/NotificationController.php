<?php

namespace App\Http\Controllers\api\v2;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\OrderNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $user = User::findOrFail(auth()->id());
        $notifications =  $user->notifications()->where('type', 'App\Notifications\OrderNotification')->latest()->take(20)->get();

        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success'    => true,
            'status'     => 200,
            'data'       => $notifications
        ]);

    }
}
