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
        $employeeName = $this->swap->employee->name ?? 'Karyawan';
        $origDate = \Carbon\Carbon::parse($this->swap->original_work_date)->locale('id')->translatedFormat('d F Y');
        $targetDate = \Carbon\Carbon::parse($this->swap->target_work_date)->locale('id')->translatedFormat('d F Y');

        return [
            'type' => 'schedule',
            'category' => 'schedule',
            'icon' => 'calendar',
            'title' => 'Pengajuan Tukar Jadwal Pribadi',
            'message' => $employeeName . ' mengajukan tukar jadwal pribadi dari ' . $origDate . ' ke ' . $targetDate . ', menunggu persetujuan Anda.',
            'route' => 'schedule_swap_list',
            'entity_id' => $this->swap->id,
            'entity_type' => 'swap_personal',
            'params' => [
                'id' => $this->swap->id,
            ],
            'link' => route('owner.management.schedules.swap-personal.index')
        ];
    }
}
