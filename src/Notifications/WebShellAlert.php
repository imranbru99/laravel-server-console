<?php

namespace Imran\WebShell\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WebShellAlert extends Notification
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string $title,
        public array $payload = [],
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return array_values((array) config('webshell.notifications.channels', ['mail']));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->line($this->title);

        foreach ($this->payload as $key => $value) {
            if (is_scalar($value)) {
                $mail->line($key.': '.$value);
            }
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'payload' => $this->payload,
        ];
    }
}
