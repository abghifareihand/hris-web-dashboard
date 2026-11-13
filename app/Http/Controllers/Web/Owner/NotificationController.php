<?php

namespace App\Http\Controllers\Web\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(15);

        return Inertia::render('Owner/Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function read($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $link = $notification->data['link'] ?? route('owner.dashboard');
        return redirect($link);
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return redirect()->back()->with('success', 'Semua notifikasi berhasil ditandai telah dibaca.');
    }

    public function poll()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'unreadNotificationsCount' => 0,
                'recentNotifications' => [],
            ]);
        }

        $unreadCount = $user->unreadNotifications()->count();
        $recent = $user->notifications()->latest()->take(5)->get()->map(function ($n) use ($user) {
            return [
                'id' => $n->id,
                'type' => $n->data['type'] ?? 'system',
                'title' => $n->data['title'] ?? 'Pemberitahuan Sistem',
                'message' => $n->data['message'] ?? $n->data['content'] ?? '',
                'link' => ($user->role === 'owner') ? route('owner.notifications.read', $n->id) : '#',
                'read_at' => $n->read_at,
                'created_at' => $n->created_at ? $n->created_at->toISOString() : null,
                'time_ago' => $n->created_at ? $n->created_at->diffForHumans() : '',
            ];
        });

        return response()->json([
            'unreadNotificationsCount' => $unreadCount,
            'recentNotifications' => $recent,
        ]);
    }
}
