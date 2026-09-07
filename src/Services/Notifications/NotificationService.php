<?php

namespace Imran\WebShell\Services\Notifications;

use Illuminate\Support\Facades\Notification;
use Imran\WebShell\Notifications\WebShellAlert;

class NotificationService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function send(string $event, string $title, array $payload = []): void
    {
        if (! config('webshell.notifications.enabled')) {
            return;
        }

        if (! config("webshell.notifications.on.{$event}", false)) {
            return;
        }

        $routes = (array) config('webshell.notifications.routes', []);

        foreach ($routes as $channel => $address) {
            if (! $address) {
                continue;
            }

            Notification::route($channel, $address)->notify(new WebShellAlert($title, $payload));
        }
    }
}
