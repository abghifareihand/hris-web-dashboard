<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewEmployeeLeavePendingRequest extends Notification
{
    use Queueable;

    public $leave;

    /**
     * Create a new notification instance.
     */
    public function __construct(LeaveRequest $leave)
    {
        $this->leave = $leave;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'leave',
            'icon' => 'calendar',
            'title' => 'Pengajuan Cuti Baru',
            'message' => 'Karyawan bernama "' . ($this->leave->employee->name ?? 'Unknown') . '" mengajukan cuti dan menunggu persetujuan Anda.',
            'link' => route('owner.management.leaves.pending.index')
        ];
    }
}
