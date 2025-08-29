<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with('employee.employer')
            ->where('status', 'unread')
            ->get();

        $groupedNotifications = $notifications->groupBy('type');

        return view('notifications.index', ['groupedNotifications' => $groupedNotifications]);
    }
}
