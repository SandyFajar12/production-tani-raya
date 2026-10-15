<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class AppNotification extends Notification
{
    protected $kind;
    protected $title;
    protected $body;
    protected $url;

    public function __construct(string $kind, string $title, string $body, ?string $url = null)
    {
        $this->kind = $kind;
        $this->title = $title;
        $this->body = $body;
        $this->url = $url;
    }

    /** Sekarang hanya lonceng (database). Untuk email nanti, tambahkan 'mail' di sini. */
    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'kind' => $this->kind,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
        ];
    }
}