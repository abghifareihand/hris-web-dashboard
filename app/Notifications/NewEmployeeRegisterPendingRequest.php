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
        return [
            'type' => 'employee',
            'icon' => 'employee',
            'title' => 'Pendaftaran Karyawan',
            'message' => 'Karyawan baru bernama "' . $this->pendingEmployee->name . '" telah mendaftar dan menunggu persetujuan Anda.',
            'link' => route('owner.management.employees.pending.index')
        ];
    }
}
