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
        $employeeName = $this->reimbursement->employee->name ?? 'Karyawan';
        $amountFormatted = number_format($this->reimbursement->amount, 0, ',', '.');
        $title = $this->reimbursement->title ?? 'Biaya Operasional';
        $dateFormatted = \Carbon\Carbon::parse($this->reimbursement->date ?? $this->reimbursement->created_at)->locale('id')->translatedFormat('d F Y');

        return [
            'type' => 'reimbursement',
            'category' => 'reimbursement',
            'icon' => 'receipt',
            'title' => 'Pengajuan Reimbursement Baru',
            'message' => $employeeName . ' mengajukan reimbursement "' . $title . '" sebesar Rp ' . $amountFormatted . ' pada ' . $dateFormatted . ', menunggu persetujuan Anda.',
            'route' => 'reimbursement_list',
            'entity_id' => $this->reimbursement->id,
            'entity_type' => 'reimbursement',
            'params' => [
                'id' => $this->reimbursement->id,
            ],
            'link' => route('owner.finance.reimbursements.pending.index'),
        ];
    }
}
