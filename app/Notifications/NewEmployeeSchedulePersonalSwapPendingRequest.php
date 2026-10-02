<?php

namespace App\Notifications;

use App\Models\SwapPersonal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewEmployeeSchedulePersonalSwapPendingRequest extends Notification
{
    use Queueable;

    public $swap;

    /**
     * Create a new notification instance.
     */
    public function __construct(SwapPersonal $swap)
    {
        $this->swap = $swap;
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
            'type' => 'schedule',
            'icon' => 'calendar',
            'title' => 'Pengajuan Tukar Jadwal Pribadi',
            'message' => 'Karyawan bernama "' . ($this->swap->employee->name ?? 'Unknown') . '" mengajukan tukar jadwal pribadi dan menunggu persetujuan Anda.',
            'link' => route('owner.management.schedules.swap-personal.index')
        ];
    }
}
