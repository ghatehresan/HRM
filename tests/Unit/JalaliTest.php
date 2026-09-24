<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Jalali;
use PHPUnit\Framework\TestCase;

final class JalaliTest extends TestCase
{
    public function test_converts_nowruz_anchors(): void
    {
        $this->assertSame([1404, 1, 1], Jalali::gregorianToJalali(2025, 3, 21));
        $this->assertSame([1405, 1, 1], Jalali::gregorianToJalali(2026, 3, 21));
        $this->assertSame([2025, 3, 21], Jalali::jalaliToGregorian(1404, 1, 1));
        $this->assertSame([2026, 3, 21], Jalali::jalaliToGregorian(1405, 1, 1));
    }

    public function test_converts_known_dates(): void
    {
        // Project kickoff day.
        $this->assertSame([1405, 7, 2], Jalali::gregorianToJalali(2026, 9, 24));
        $this->assertSame([2026, 9, 24], Jalali::jalaliToGregorian(1405, 7, 2));

        // 1403 is a Jalali leap year: Esfand has 30 days.
        $this->assertSame([1403, 12, 30], Jalali::gregorianToJalali(2025, 3, 20));
        $this->assertSame([2025, 3, 20], Jalali::jalaliToGregorian(1403, 12, 30));
    }

    public function test_round_trips(): void
    {
        $samples = [
            [1990, 1, 1],
            [2000, 2, 29],
            [2024, 2, 29],
            [2024, 3, 19],
            [2024, 3, 20],
            [2025, 12, 31],
            [2026, 9, 24],
            [2030, 6, 15],
        ];

        foreach ($samples as [$year, $month, $day]) {
            [$jy, $jm, $jd] = Jalali::gregorianToJalali($year, $month, $day);

            $this->assertSame(
                [$year, $month, $day],
                Jalali::jalaliToGregorian($jy, $jm, $jd),
                "Round-trip failed for {$year}-{$month}-{$day}"
            );
        }
    }

    public function test_formats(): void
    {
        $this->assertSame('۱۴۰۵/۰۷/۰۲', Jalali::format('2026-09-24'));
        $this->assertSame('۲ مهر ۱۴۰۵', Jalali::format('2026-09-24', 'long'));
        $this->assertSame('مهر ۱۴۰۵', Jalali::format('2026-09-24', 'month'));
        $this->assertSame('۱۴۰۵/۰۷/۰۲ — ۰۰:۰۰', Jalali::format('2026-09-24', 'full'));
        $this->assertSame('۲ مهر ۱۴۰۵', Jalali::format(mktime(0, 0, 0, 9, 24, 2026), 'long'));
        $this->assertSame('—', Jalali::format(''));
        $this->assertSame('—', Jalali::format('not-a-date'));
    }

    public function test_parses_jalali_strings(): void
    {
        $this->assertSame('2026-09-24', Jalali::parseJalaliString('۱۴۰۵/۰۷/۰۲'));
        $this->assertSame('2026-09-24', Jalali::parseJalaliString('1405/7/2'));
        $this->assertSame('2026-09-24', Jalali::parseJalaliString('1405-07-02'));
        // Gregorian-looking years pass through as Gregorian dates.
        $this->assertSame('2026-09-24', Jalali::parseJalaliString('2026/09/24'));
        $this->assertNull(Jalali::parseJalaliString(''));
        $this->assertNull(Jalali::parseJalaliString('bogus'));
    }

    public function test_month_range_is_inclusive(): void
    {
        // Mehr has 30 days: 1405/07/01..30 = Sep 23 .. Oct 22.
        $this->assertSame(['2026-09-23', '2026-10-22'], Jalali::monthRange(1405, 7));
        // Esfand of a leap year ends a day later.
        $this->assertSame(['2025-02-19', '2025-03-20'], Jalali::monthRange(1403, 12));
        // Esfand of a common year.
        $this->assertSame(['2026-02-20', '2026-03-20'], Jalali::monthRange(1404, 12));
    }

    public function test_today_returns_sane_shape(): void
    {
        [$year, $month, $day] = Jalali::today();

        $this->assertGreaterThanOrEqual(1400, $year);
        $this->assertLessThanOrEqual(1500, $year);
        $this->assertGreaterThanOrEqual(1, $month);
        $this->assertLessThanOrEqual(12, $month);
        $this->assertGreaterThanOrEqual(1, $day);
        $this->assertLessThanOrEqual(31, $day);
    }
}
