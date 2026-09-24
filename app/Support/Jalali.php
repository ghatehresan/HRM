<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Jalali (Shamsi) calendar conversions.
 *
 * Ported from the production helpers of ghatehresan-management-system
 * (app/helpers.php) and hardened with strict types. The algorithm is the
 * standard 33-year-cycle implementation, accurate for Jalali years ~1300–1500.
 *
 * RULES (see ARCHITECTURE.md):
 *  - Timestamps are ALWAYS stored Gregorian/UTC. Jalali exists ONLY in the
 *    presentation layer.
 *  - format() interprets its input in the app timezone (UTC). Date-only
 *    values (Y-m-d) are unaffected by timezones.
 */
final class Jalali
{
    private function __construct() {}

    /**
     * Persian month names, 1-indexed (index 0 is a placeholder).
     *
     * @var array<int, string>
     */
    public const MONTHS = [
        '',
        'فروردین',
        'اردیبهشت',
        'خرداد',
        'تیر',
        'مرداد',
        'شهریور',
        'مهر',
        'آبان',
        'آذر',
        'دی',
        'بهمن',
        'اسفند',
    ];

    /**
     * @return array{int, int, int} [jalaliYear, jalaliMonth, jalaliDay]
     */
    public static function gregorianToJalali(int $gy, int $gm, int $gd): array
    {
        $gDaysInMonth = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + ((int) (($gy2 + 3) / 4))
            - ((int) (($gy2 + 99) / 100)) + ((int) (($gy2 + 399) / 400))
            + $gd + $gDaysInMonth[$gm - 1];
        $jy = -1595 + (33 * ((int) ($days / 12053)));
        $days %= 12053;
        $jy += 4 * ((int) ($days / 1461));
        $days %= 1461;
        if ($days > 365) {
            $jy += (int) (($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        if ($days < 186) {
            $jm = 1 + (int) ($days / 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + (int) (($days - 186) / 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }

    /**
     * @return array{int, int, int} [gregorianYear, gregorianMonth, gregorianDay]
     */
    public static function jalaliToGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355668 + (365 * $jy) + (((int) ($jy / 33)) * 8)
            + ((int) ((($jy % 33) + 3) / 4)) + $jd
            + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
        $gy = 400 * ((int) ($days / 146097));
        $days %= 146097;
        if ($days > 36524) {
            $gy += 100 * ((int) (--$days / 36524));
            $days %= 36524;
            if ($days >= 365) {
                $days++;
            }
        }
        $gy += 4 * ((int) ($days / 1461));
        $days %= 1461;
        if ($days > 365) {
            $gy += (int) (($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        $gd = $days + 1;
        $isLeap = (($gy % 4 === 0 && $gy % 100 !== 0) || ($gy % 400 === 0));
        $gMonthLengths = [0, 31, $isLeap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $gm = 0;
        while ($gm < 13 && $gd > $gMonthLengths[$gm]) {
            $gd -= $gMonthLengths[$gm];
            $gm++;
        }

        return [$gy, $gm, $gd];
    }

    /**
     * Format a Gregorian date as Jalali with Persian digits.
     *
     * @param  string|int  $value  'Y-m-d', a datetime string, or a unix timestamp
     * @param  string  $format  short|long|month|full
     */
    public static function format(string|int $value, string $format = 'short'): string
    {
        if ($value === '' || $value === 0 || $value === '0') {
            return '—';
        }

        $timestamp = is_numeric($value) ? (int) $value : strtotime((string) $value);

        if ($timestamp === false || $timestamp <= 0) {
            return '—';
        }

        [$jy, $jm, $jd] = self::gregorianToJalali(
            (int) date('Y', $timestamp),
            (int) date('n', $timestamp),
            (int) date('j', $timestamp),
        );

        return match ($format) {
            'long' => PersianNumbers::toFa((string) $jd).' '.self::MONTHS[$jm].' '.PersianNumbers::toFa((string) $jy),
            'month' => self::MONTHS[$jm].' '.PersianNumbers::toFa((string) $jy),
            'full' => PersianNumbers::toFa(sprintf('%04d/%02d/%02d', $jy, $jm, $jd)).' — '.PersianNumbers::toFa(date('H:i', $timestamp)),
            default => PersianNumbers::toFa(sprintf('%04d/%02d/%02d', $jy, $jm, $jd)),
        };
    }

    /**
     * Today as a Jalali date.
     *
     * @return array{int, int, int} [jalaliYear, jalaliMonth, jalaliDay]
     */
    public static function today(): array
    {
        return self::gregorianToJalali((int) date('Y'), (int) date('n'), (int) date('j'));
    }

    /**
     * Convert a Jalali 'Y/m/d' string (Persian or Latin digits) to Gregorian 'Y-m-d'.
     *
     * Gregorian-looking years (> 1700) pass through as Gregorian dates.
     */
    public static function parseJalaliString(string $value): ?string
    {
        $value = PersianNumbers::toEn(trim($value));

        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', $value, $matches) !== 1) {
            return null;
        }

        $year = (int) ($matches[1] ?? 0);

        if ($year > 1700) {
            return sprintf('%04d-%02d-%02d', $year, (int) ($matches[2] ?? 0), (int) ($matches[3] ?? 0));
        }

        [$gy, $gm, $gd] = self::jalaliToGregorian($year, (int) ($matches[2] ?? 0), (int) ($matches[3] ?? 0));

        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }

    /**
     * Gregorian [start, end] of a Jalali month. Both ends are INCLUSIVE,
     * because every query against a month range uses BETWEEN.
     *
     * Implemented with pure calendar arithmetic on purpose: timestamp
     * subtraction (strtotime '-1 day') at midnight is DST-fragile and
     * can produce off-by-one days (historic Asia/Tehran transitions
     * happened exactly at midnight).
     *
     * @return array{string, string}
     */
    public static function monthRange(int $jy, int $jm): array
    {
        [$gy1, $gm1, $gd1] = self::jalaliToGregorian($jy, $jm, 1);
        $nextYear = $jm === 12 ? $jy + 1 : $jy;
        $nextMonth = $jm === 12 ? 1 : $jm + 1;
        [$gy2, $gm2, $gd2] = self::jalaliToGregorian($nextYear, $nextMonth, 1);

        $start = sprintf('%04d-%02d-%02d', $gy1, $gm1, $gd1);

        // End = the calendar day before the next Jalali month starts.
        if ($gd2 > 1) {
            $end = sprintf('%04d-%02d-%02d', $gy2, $gm2, $gd2 - 1);
        } else {
            $prevMonth = $gm2 === 1 ? 12 : $gm2 - 1;
            $prevYear = $gm2 === 1 ? $gy2 - 1 : $gy2;
            $end = sprintf('%04d-%02d-%02d', $prevYear, $prevMonth, self::gregorianMonthLength($prevYear, $prevMonth));
        }

        return [$start, $end];
    }

    /**
     * Days in a Gregorian month. Pure integer math — no timezone, no
     * timestamp, no extension dependency.
     */
    private static function gregorianMonthLength(int $year, int $month): int
    {
        return match ($month) {
            2 => (($year % 4 === 0 && $year % 100 !== 0) || $year % 400 === 0) ? 29 : 28,
            4, 6, 9, 11 => 30,
            default => 31,
        };
    }
}
