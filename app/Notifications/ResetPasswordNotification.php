<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    /**
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ], false));

        return (new MailMessage)
            ->subject('بازیابی رمز عبور')
            ->greeting('سلام '.$notifiable->name.'،')
            ->line('درخواست بازیابی رمز عبور برای حساب شما ثبت شد.')
            ->action('تعیین رمز جدید', $url)
            ->line('این پیوند ۶۰ دقیقه اعتبار دارد و فقط یک‌بار قابل استفاده است.')
            ->line('اگر شما این درخواست را ثبت نکرده‌اید، این پیام را نادیده بگیرید.')
            ->salutation('منابع انسانی قطعه‌رسان');
    }
}
