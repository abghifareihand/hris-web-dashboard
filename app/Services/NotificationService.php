<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send in-app notification to a specific user.
     *
     * @param User $user
     * @param array $data [
     *   'title'       => string,
     *   'message'     => string,
     *   'type'        => string ('leave', 'overtime', 'loan', 'reimbursement', 'announcement', 'payroll', 'schedule', 'employee', etc.),
     *   'route'       => ?string (target list screen route in mobile Flutter, e.g. 'leave_list'),
     *   'entity_id'   => mixed (id of leave, loan, etc.),
     *   'entity_type' => ?string ('leave_request', 'loan', etc.),
     *   'params'      => ?array (navigation arguments, e.g. ['id' => 12]),
     *   'link'        => ?string (web URL fallback),
     *   'icon'        => ?string ('calendar', 'clock', 'wallet', 'receipt', 'megaphone', 'banknotes', etc.)
     * ]
     */
    public static function sendToUser(User $user, array $data): void
    {
        try {
            $notification = new AppNotification($data);
            $user->notify($notification);

            // =========================================================================
            // FCM PUSH NOTIFICATION HOOK (FUTURE CONFIGURATION)
            // =========================================================================
            // Saat akun Firebase Cloud Messaging (FCM) sudah disetup,
            // baris ini tinggal diaktifkan untuk mengirim push notif ke HP Android / iOS.
            //
            // if (!empty($user->fcm_token)) {
            //     self::sendFcmPush($user->fcm_token, $data);
            // }
        } catch (\Throwable $e) {
            Log::error('NotificationService error [sendToUser]: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'data'    => $data,
            ]);
        }
    }

    /**
     * Send in-app notification to the Owner of a Company.
     */
    public static function sendToOwner(Company $company, array $data): void
    {
        $owner = $company->owner;
        if ($owner) {
            self::sendToUser($owner, $data);
        }
    }

    /**
     * Send in-app notification to an Employee via their linked User account.
     */
    public static function sendToEmployee(Employee $employee, array $data): void
    {
        $user = $employee->user;
        if ($user) {
            self::sendToUser($user, $data);
        }
    }

    /**
     * Send in-app notification to multiple employees (e.g. for Announcements).
     *
     * @param iterable|array $employees List of Employee or User models
     * @param array $data
     */
    public static function sendToEmployees(iterable $employees, array $data): void
    {
        foreach ($employees as $emp) {
            if ($emp instanceof Employee) {
                self::sendToEmployee($emp, $data);
            } elseif ($emp instanceof User) {
                self::sendToUser($emp, $data);
            }
        }
    }

    /**
     * FCM Push Notification dispatcher placeholder.
     * Configure this method when Firebase FCM server key / credentials are ready.
     */
    protected static function sendFcmPush(string $fcmToken, array $data): void
    {
        // TODO: Integrasikan FCM service (contoh: Kreait Firebase / HTTP v1 API)
        // Log::info('FCM push dispatch placeholder to token: ' . substr($fcmToken, 0, 10) . '...');
    }
}
