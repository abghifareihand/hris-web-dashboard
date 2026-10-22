<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewEmployeeLoanPendingRequest extends Notification
{
    use Queueable;

    public $loan;

    /**
     * Create a new notification instance.
     */
    public function __construct(Loan $loan)
    {
        $this->loan = $loan;
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
        $employeeName = $this->loan->employee->name ?? 'Unknown';
        $amountFormatted = number_format($this->loan->amount, 0, ',', '.');

        return [
            'type' => 'loan',
            'icon' => 'wallet',
            'title' => 'Pengajuan Kasbon Baru',
            'message' => 'Karyawan bernama "' . $employeeName . '" mengajukan pinjaman kasbon sebesar Rp ' . $amountFormatted . ' dan menunggu persetujuan Anda.',
            'link' => route('owner.finance.loans.pending.index'),
        ];
    }
}
