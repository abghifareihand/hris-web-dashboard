<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Get paginated notifications for the authenticated user.
     *
     * Query Parameters:
     * - status: 'all' (default), 'unread', 'read'
     * - type: optional string ('leave', 'overtime', 'loan', 'reimbursement', 'announcement', 'payroll', 'schedule', 'employee', etc.)
     * - per_page: integer (default: 15)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Determine base query according to read status
        $status = strtolower($request->input('status', 'all'));
        if ($status === 'unread') {
            $query = $user->unreadNotifications();
        } elseif ($status === 'read') {
            $query = $user->readNotifications();
        } else {
            $query = $user->notifications();
        }

        // 2. Filter by category/type if specified
        if ($request->filled('type') && $request->type !== 'all') {
            $type = $request->type;
            $query->where(function ($q) use ($type) {
                $q->where('data->type', $type)
                  ->orWhere('data->category', $type);
            });
        }

        // 3. Total unread badge count for mobile app bar / navbar
        $unreadCount = $user->unreadNotifications()->count();

        // 4. Paginate results
        $perPage = $this->getPerPage($request, 15);
        $paginated = $query->paginate($perPage);

        // 5. Transform each notification to standard structure
        $paginated->getCollection()->transform(function (DatabaseNotification $item) {
            return $this->formatNotification($item);
        });

        return response()->json([
            'status'       => true,
            'message'      => 'Daftar notifikasi berhasil diambil.',
            'unread_count' => $unreadCount,
            'data'         => $paginated->items(),
            'meta'         => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
            ],
        ]);
    }

    /**
     * Get unread notifications badge count.
     * Lightweight endpoint designed for periodic polling or dashboard header display.
     */
    public function unreadCount(Request $request)
    {
        $unreadCount = $request->user()->unreadNotifications()->count();

        return response()->json([
            'status'  => true,
            'message' => 'Jumlah notifikasi belum dibaca berhasil diambil.',
            'data'    => [
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return response()->json([
                'status'  => false,
                'message' => 'Notifikasi tidak ditemukan.',
            ], 404);
        }

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'status'  => true,
            'message' => 'Notifikasi berhasil ditandai telah dibaca.',
            'data'    => [
                'id'           => $notification->id,
                'is_read'      => true,
                'read_at'      => Carbon::now()->toIso8601String(),
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * Mark all notifications of the authenticated user as read.
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'status'  => true,
            'message' => 'Semua notifikasi berhasil ditandai telah dibaca.',
            'data'    => [
                'unread_count' => 0,
            ],
        ]);
    }

    /**
     * Delete a single notification.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return response()->json([
                'status'  => false,
                'message' => 'Notifikasi tidak ditemukan.',
            ], 404);
        }

        $notification->delete();
        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'status'  => true,
            'message' => 'Notifikasi berhasil dihapus.',
            'data'    => [
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    /**
     * Format DatabaseNotification model into clean API payload.
     */
    protected function formatNotification(DatabaseNotification $item): array
    {
        $data = is_array($item->data) ? $item->data : (json_decode($item->data, true) ?: []);

        $type = $data['type'] ?? ($data['category'] ?? 'general');

        // Target navigation route for mobile Flutter app (navigates to list/daftar data)
        $route = $data['route'] ?? ($data['page'] ?? null);
        if (!$route || str_ends_with($route, '_detail')) {
            $route = match ($type) {
                'employee', 'pending_employee' => 'pending_employee_list',
                'leave'                         => 'leave_list',
                'overtime'                      => 'overtime_list',
                'loan'                          => 'loan_list',
                'reimbursement'                 => 'reimbursement_list',
                'schedule'                      => 'schedule_swap_list',
                'announcement'                  => 'announcement_list',
                'payroll'                       => 'payroll_list',
                default                         => $route ?? null,
            };
        }

        // Entity ID and navigation parameters
        $entityId = $data['entity_id'] ?? ($data['id'] ?? null);
        $params = $data['params'] ?? ($data['action_params'] ?? ($entityId ? ['id' => $entityId] : []));

        $createdAt = Carbon::parse($item->created_at);
        $readAt = $item->read_at ? Carbon::parse($item->read_at) : null;

        return [
            'id'                   => $item->id,
            'title'                => $data['title'] ?? 'Pemberitahuan Sistem',
            'message'              => $data['message'] ?? ($data['content'] ?? ''),
            'type'                 => $type,
            'category'             => $data['category'] ?? $type,
            'icon'                 => $data['icon'] ?? 'bell',
            'route'                => $route,
            'entity_id'            => $entityId,
            'entity_type'          => $data['entity_type'] ?? null,
            'params'               => $params,
            'link'                 => $data['link'] ?? null,
            'is_read'              => $readAt !== null,
            'read_at'              => $readAt?->toIso8601String(),
            'created_at'           => $createdAt->toIso8601String(),
            'created_at_formatted' => $createdAt->translatedFormat('d M Y, H:i'),
            'time_ago'             => $createdAt->diffForHumans(),
        ];
    }
}
