<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppNotification extends Notification
{
    use Queueable;

    public string $title;
    public string $message;
    public string $type;
    public ?string $route;
    public mixed $entityId;
    public ?string $entityType;
    public array $params;
    public ?string $link;
    public ?string $icon;

    /**
     * Create a new notification instance.
     *
     * @param array $data [
     *   'title'       => string,
     *   'message'     => string,
     *   'type'        => string ('leave', 'overtime', 'loan', 'reimbursement', 'announcement', 'payroll', 'schedule', 'general', etc.),
     *   'route'       => ?string (mobile navigation target list route, e.g. 'leave_list'),
     *   'entity_id'   => mixed,
     *   'entity_type' => ?string,
     *   'params'      => ?array,
     *   'link'        => ?string (web url fallback),
     *   'icon'        => ?string ('calendar', 'clock', 'wallet', 'receipt', 'megaphone', 'banknotes', etc.)
     * ]
     */
    public function __construct(array $data)
    {
        $this->title = $data['title'] ?? 'Pemberitahuan Sistem';
        $this->message = $data['message'] ?? '';
        $this->type = $data['type'] ?? 'general';
        $this->route = $data['route'] ?? ($data['page'] ?? null);
        $this->entityId = $data['entity_id'] ?? ($data['id'] ?? null);
        $this->entityType = $data['entity_type'] ?? null;
        $this->params = $data['params'] ?? ($this->entityId ? ['id' => $this->entityId] : []);
        $this->link = $data['link'] ?? null;
        $this->icon = $data['icon'] ?? 'bell';
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
            'title'       => $this->title,
            'message'     => $this->message,
            'type'        => $this->type,
            'category'    => $this->type,
            'icon'        => $this->icon,
            'route'       => $this->route,
            'entity_id'   => $this->entityId,
            'entity_type' => $this->entityType,
            'params'      => $this->params,
            'link'        => $this->link,
        ];
    }
}
