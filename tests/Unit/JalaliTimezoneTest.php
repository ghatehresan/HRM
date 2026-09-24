<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Jalali;
use PHPUnit\Framework\TestCase;

final class JalaliTimezoneTest extends TestCase
{
    public function test_tehran_wall_clock_stays_on_the_same_day(): void
    {
        // 2026-09-24 20:00 UTC = 23:30 in Tehran (UTC+3:30, no DST).
        $this->assertSame(
            '۱۴۰۵/۰۷/۰۲',
            Jalali::formatInTimezone('2026-09-24 20:00:00', 'Asia/Tehran', 'short')
        );
    }

    public function test_tehran_wall_clock_crosses_midnight(): void
    {
        // 2026-09-24 21:00 UTC = 00:30 the next day in Tehran.
        $this->assertSame(
            '۱۴۰۵/۰۷/۰۳',
            Jalali::formatInTimezone('2026-09-24 21:00:00', 'Asia/Tehran', 'short')
        );
    }

    public function test_format_in_timezone_accepts_datetime_and_timestamp(): void
    {
        $this->assertSame(
            '۲ مهر ۱۴۰۵',
            Jalali::formatInTimezone(new \DateTimeImmutable('2026-09-24 20:00:00+00:00'), 'Asia/Tehran', 'long')
        );

        $this->assertSame(
            '۱۴۰۵/۰۷/۰۲',
            Jalali::formatInTimezone(gmmktime(0, 0, 0, 9, 24, 2026), 'UTC', 'short')
        );
    }

    public function test_format_in_timezone_rejects_garbage(): void
    {
        $this->assertSame('—', Jalali::formatInTimezone('not-a-date', 'Asia/Tehran'));
        $this->assertSame('—', Jalali::formatInTimezone(0, 'Asia/Tehran'));
    }
}
