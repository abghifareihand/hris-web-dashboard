<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Api\NotificationController as BaseNotificationController;
use Illuminate\Http\Request;

class NotificationController extends BaseNotificationController
{
    /**
     * Employee-specific notification controller.
     * Inherits index, unreadCount, markAsRead, markAllAsRead, and destroy.
     */
}
