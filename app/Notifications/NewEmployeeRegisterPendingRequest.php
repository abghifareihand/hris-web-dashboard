<?php

namespace App\Notifications;

use App\Models\PendingEmployee;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewEmployeeRegisterPendingRequest extends Notification
{
    use Queueable;

    public $pendingEmployee;

    /**
     * Create a new notification instance.
     */
    public function __construct(PendingEmployee $pendingEmployee)
    {
        $this->pendingEmployee = $pendingEmployee;
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
        $employeeName = $this->pendingEmployee->name ?? 'Calon Karyawan';
        $dateFormatted = \Carbon\Carbon::parse($this->pendingEmployee->created_at)->locale('id')->translatedFormat('d F Y');

        return [
            'type' => 'employee',
            'category' => 'employee',
            'icon' => 'employee',
            'title' => 'Pendaftaran Karyawan Baru',
            'message' => $employeeName . ' mendaftar sebagai karyawan pada ' . $dateFormatted . ', menunggu persetujuan Anda.',
            'route' => 'pending_employee_list',
            'entity_id' => $this->pendingEmployee->id,
            'entity_type' => 'pending_employee',
            'params' => [
                'id' => $this->pendingEmployee->id,
            ],
            'link' => route('owner.management.employees.pending.index')
        ];
    }
}
