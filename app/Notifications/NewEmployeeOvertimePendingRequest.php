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
        $employeeName = $this->overtime->employee->name ?? 'Karyawan';
        $dateFormatted = \Carbon\Carbon::parse($this->overtime->date)->locale('id')->translatedFormat('d F Y');

        return [
            'type' => 'overtime',
            'category' => 'overtime',
            'icon' => 'clock',
            'title' => 'Pengajuan Lembur Baru',
            'message' => $employeeName . ' mengajukan lembur pada ' . $dateFormatted . ', menunggu persetujuan Anda.',
            'route' => 'overtime_list',
            'entity_id' => $this->overtime->id,
            'entity_type' => 'overtime',
            'params' => [
                'id' => $this->overtime->id,
            ],
            'link' => route('owner.management.overtimes.pending.index')
        ];
    }
}
