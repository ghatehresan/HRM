<?php

namespace App\Notifications;

use App\Support\Jalali;
use App\Support\PersianNumbers;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class AccountLockedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ?Carbon $lockedUntil) {}

    /**
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('حساب شما موقتاً قفل شد')
            ->greeting('سلام '.$notifiable->name.'،')
            ->line('به دلیل چند تلاش ناموفق برای ورود، حساب شما موقتاً قفل شد.');

        if ($this->lockedUntil !== null) {
            $tehran = $this->lockedUntil->copy()->setTimezone((string) config('hrm.display_timezone', 'Asia/Tehran'));
            $message->line(
                'زمان بازگشایی: '.Jalali::format($tehran->format('Y-m-d'), 'long')
                .' ساعت '.PersianNumbers::toFa($tehran->format('H:i'))
            );
        }

        return $message
            ->line('اگر این تلاش‌ها کار شما نبوده، لطفاً پس از ورود رمز خود را تغییر دهید.')
            ->salutation('منابع انسانی قطعه‌رسان');
    }
}
