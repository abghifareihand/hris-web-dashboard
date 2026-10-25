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
        $employeeName = $this->leave->employee->name ?? 'Karyawan';
        $startDate = \Carbon\Carbon::parse($this->leave->start_date)->locale('id')->translatedFormat('d F Y');
        $endDate = \Carbon\Carbon::parse($this->leave->end_date)->locale('id')->translatedFormat('d F Y');
        $dateText = ($this->leave->start_date === $this->leave->end_date)
            ? $startDate
            : $startDate . ' s/d ' . $endDate;

        return [
            'type' => 'leave',
            'category' => 'leave',
            'icon' => 'calendar',
            'title' => 'Pengajuan Cuti Baru',
            'message' => $employeeName . ' mengajukan cuti pada ' . $dateText . ', menunggu persetujuan Anda.',
            'route' => 'leave_list',
            'entity_id' => $this->leave->id,
            'entity_type' => 'leave_request',
            'params' => [
                'id' => $this->leave->id,
            ],
            'link' => route('owner.management.leaves.pending.index')
        ];
    }
}
