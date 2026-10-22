<?php

namespace App\Notifications;

use App\Models\Reimbursement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewEmployeeReimbursementPendingRequest extends Notification
{
    use Queueable;

    public $reimbursement;

    /**
     * Create a new notification instance.
     */
    public function __construct(Reimbursement $reimbursement)
    {
        $this->reimbursement = $reimbursement;
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
        $employeeName = $this->reimbursement->employee->name ?? 'Unknown';
        $amountFormatted = number_format($this->reimbursement->amount, 0, ',', '.');
        $title = $this->reimbursement->title ?? 'Klaim Biaya';

        return [
            'type' => 'reimbursement',
            'icon' => 'receipt',
            'title' => 'Pengajuan Reimbursement Baru',
            'message' => 'Karyawan bernama "' . $employeeName . '" mengajukan klaim reimbursement "' . $title . '" sebesar Rp ' . $amountFormatted . ' dan menunggu persetujuan Anda.',
            'link' => route('owner.finance.reimbursements.pending.index'),
        ];
    }
}
