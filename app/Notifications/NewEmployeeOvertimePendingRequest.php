<?php

namespace App\Notifications;

use App\Models\Overtime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewEmployeeOvertimePendingRequest extends Notification
{
    use Queueable;

    public $overtime;

    /**
     * Create a new notification instance.
     */
    public function __construct(Overtime $overtime)
    {
        $this->overtime = $overtime;
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
            'type' => 'overtime',
            'icon' => 'clock',
            'title' => 'Pengajuan Lembur Baru',
            'message' => 'Karyawan bernama "' . ($this->overtime->employee->name ?? 'Unknown') . '" mengajukan lembur dan menunggu persetujuan Anda.',
            'link' => route('owner.management.overtimes.pending.index')
        ];
    }
}
