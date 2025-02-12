<?php

namespace App\Http\Controllers\Seller;

use App\Models\User;

class NotificationController extends Controller
{
    public function index() {
        $user = User::findOrFail(auth()->id());
        $notifications = $user->notifications()->paginate(15);
        auth()->user()->unreadNotifications->markAsRead();

        return view('seller.notification.index', compact('notifications'));
    }
}
