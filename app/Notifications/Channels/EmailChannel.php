<?php

namespace App\Notifications\Channels;

use Illuminate\Mail\Message;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

class EmailChannel
{
    public function send(SendsEmail $notifiable, Notification $notification): void
    {
        $this->bootConfigs($notifiable);

        $bcc = $notifiable->routeNotificationForEmail('test_recipients');
        if (count($bcc) === 0) {
            if ($notifiable instanceof \App\Models\Team) {
                $bcc = $notifiable->members()->pluck('email')->toArray();
            }
        }
        $mailMessage = $notification->toMail($notifiable);
        Mail::send(
            [],
            [],
            fn (Message $message) => $message
                ->from(
                    data_get($notifiable, 'smtp_from_address'),
                    data_get($notifiable, 'smtp_from_name'),
                )
                ->bcc($bcc)
                ->subject($mailMessage->subject)
                ->html((string)$mailMessage->render())
        );
    }

    private function bootConfigs($notifiable): void
    {
        $password = data_get($notifiable, 'smtp_password');
        if ($password) $password = decrypt($password);

        config()->set('mail.default', 'smtp');
        config()->set('mail.mailers.smtp', [
            "transport" => "smtp",
            "host" => data_get($notifiable, 'smtp_host'),
            "port" => data_get($notifiable, 'smtp_port'),
            "encryption" => data_get($notifiable, 'smtp_encryption'),
            "username" => data_get($notifiable, 'smtp_username'),
            "password" => $password,
            "timeout" => data_get($notifiable, 'smtp_timeout'),
            "local_domain" => null,
        ]);
    }
}