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
        $employeeName = $this->loan->employee->name ?? 'Karyawan';
        $amountFormatted = number_format($this->loan->amount, 0, ',', '.');
        $dateFormatted = \Carbon\Carbon::parse($this->loan->date)->locale('id')->translatedFormat('d F Y');

        return [
            'type' => 'loan',
            'category' => 'loan',
            'icon' => 'wallet',
            'title' => 'Pengajuan Pinjaman Baru',
            'message' => $employeeName . ' mengajukan pinjaman sebesar Rp ' . $amountFormatted . ' pada ' . $dateFormatted . ', menunggu persetujuan Anda.',
            'route' => 'loan_list',
            'entity_id' => $this->loan->id,
            'entity_type' => 'loan',
            'params' => [
                'id' => $this->loan->id,
            ],
            'link' => route('owner.finance.loans.pending.index'),
        ];
    }
}
