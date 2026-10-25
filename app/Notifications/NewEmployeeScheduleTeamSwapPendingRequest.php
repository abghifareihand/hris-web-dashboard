<?php

namespace App\Notifications;

use App\Models\SwapTeam;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewEmployeeScheduleTeamSwapPendingRequest extends Notification
{
    use Queueable;

    public $swap;

    /**
     * Create a new notification instance.
     */
    public function __construct(SwapTeam $swap)
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
        $requestorName = $this->swap->requestor->name ?? 'Karyawan';
        $targetName = $this->swap->targetEmployee->name ?? 'Rekan Kerja';
        $origDate = \Carbon\Carbon::parse($this->swap->requestor_work_date)->locale('id')->translatedFormat('d F Y');
        $targetDate = \Carbon\Carbon::parse($this->swap->target_work_date)->locale('id')->translatedFormat('d F Y');
        $dateText = ($this->swap->requestor_work_date->format('Y-m-d') === $this->swap->target_work_date->format('Y-m-d'))
            ? $origDate
            : $origDate . ' dengan ' . $targetDate;

        return [
            'type' => 'schedule',
            'category' => 'schedule',
            'icon' => 'calendar',
            'title' => 'Pengajuan Tukar Jadwal Tim',
            'message' => $requestorName . ' mengajukan tukar jadwal tim dengan ' . $targetName . ' pada ' . $dateText . ', menunggu persetujuan Anda.',
            'route' => 'schedule_swap_list',
            'entity_id' => $this->swap->id,
            'entity_type' => 'swap_team',
            'params' => [
                'id' => $this->swap->id,
            ],
            'link' => route('owner.management.schedules.swap-team.index')
        ];
    }
}
