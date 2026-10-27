<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Get paginated notifications for the authenticated Owner.
     *
     * Query Parameters:
     * - status: 'all' (default), 'unread', 'read'
     * - type: optional string ('employee', 'pending_employee', 'leave', 'overtime', 'loan', 'reimbursement', 'schedule', 'announcement', 'payroll', 'feedback', etc.)
     * - date: optional string (YYYY-MM-DD) for specific day filter
     * - start_date: optional string (YYYY-MM-DD) for date range start
     * - end_date: optional string (YYYY-MM-DD) for date range end
     * - per_page: integer (default: 15)
     * - page: integer (default: 1)
     */
    public function index(Request $request): JsonResponse
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

        // 3. Filter by date if specified (single date or date range)
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // 3. Total unread badge count for mobile app bar / navbar
        $unreadCount = $user->unreadNotifications()->count();

        // 4. Paginate results
        $perPage = $this->getPerPage($request, 15);
        $paginated = $query->latest()->paginate($perPage);

        // 5. Transform each notification to standard structure
        $paginated->getCollection()->transform(function (DatabaseNotification $item) {
            return $this->formatNotification($item);
        });

        return response()->json([
            'status'       => true,
            'message'      => 'Daftar notifikasi owner berhasil diambil.',
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
     * Get unread notifications badge count for Owner.
     * Lightweight endpoint designed for periodic polling or dashboard header badge.
     */
    public function unreadCount(Request $request): JsonResponse
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
     * Mark a single notification as read for Owner.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
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
     * Mark all notifications of Owner as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
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
     * Delete a single notification for Owner.
     */
    public function destroy(Request $request, string $id): JsonResponse
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
     * Format DatabaseNotification model into clean API payload for Owner Mobile App.
     */
    protected function formatNotification(DatabaseNotification $item): array
    {
        $data = is_array($item->data) ? $item->data : (json_decode($item->data, true) ?: []);

        $type = $data['type'] ?? ($data['category'] ?? 'general');

        // Target navigation route for Owner Flutter mobile app
        $route = $data['route'] ?? ($data['page'] ?? null);
        if (!$route || str_ends_with($route, '_detail')) {
            $route = match ($type) {
                'employee', 'pending_employee' => 'pending_employee_list',
                'leave'                         => 'leave_list',
                'overtime'                      => 'overtime_list',
                'loan'                          => 'loan_list',
                'reimbursement'                 => 'reimbursement_list',
                'schedule', 'schedule_swap'     => 'schedule_swap_list',
                'announcement'                  => 'announcement_list',
                'payroll'                       => 'payroll_list',
                'feedback'                      => 'feedback_list',
                default                         => $route ?? null,
            };
        }

        // Entity ID
        $entityId = $data['entity_id'] ?? ($data['id'] ?? null);

        $createdAt = Carbon::parse($item->created_at)->locale('id');
        $readAt = $item->read_at ? Carbon::parse($item->read_at) : null;

        return [
            'id'                   => (string) $item->id,
            'title'                => $data['title'] ?? 'Pemberitahuan Sistem',
            'message'              => $data['message'] ?? ($data['content'] ?? ''),
            'type'                 => $type,
            'category'             => $data['category'] ?? $type,
            'icon'                 => $data['icon'] ?? 'bell',
            'route'                => $route,
            'entity_id'            => $entityId ? (int) $entityId : null,
            'entity_type'          => $data['entity_type'] ?? null,
            'link'                 => $data['link'] ?? null,
            'is_read'              => $readAt !== null,
            'read_at'              => $readAt?->toIso8601String(),
            'created_at'           => $createdAt->toIso8601String(),
            'created_at_formatted' => $createdAt->translatedFormat('d M Y, H:i'),
            'time_ago'             => $createdAt->diffForHumans(),
        ];
    }
}
