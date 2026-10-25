<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\NotificationController as BaseNotificationController;
use Illuminate\Http\Request;

class NotificationController extends BaseNotificationController
{
    /**
     * Owner-specific notification controller.
     * Inherits index, unreadCount, markAsRead, markAllAsRead, and destroy.
     */
}
