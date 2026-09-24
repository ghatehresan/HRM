<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Persian/Arabic/Latin digit conversion and money formatting.
 *
 * Ported from ghatehresan-management-system (app/helpers.php).
 *
 * BRAND RULES (see BRAND.md):
 *  - Amounts, dates and counters display in PERSIAN digits (۱۲٬۵۰۰).
 *  - Codes and identifiers stay LATIN (EMP-0142, GR-48213).
 *  - User input is normalized to Latin BEFORE validation/storage.
 */
final class PersianNumbers
{
    private function __construct() {}

    /** @var array<int, string> */
    private const EN = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /** @var array<int, string> */
    private const FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

    /** @var array<int, string> */
    private const AR = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

    public static function toFa(string|int|float $value): string
    {
        return str_replace(self::EN, self::FA, (string) $value);
    }

    public static function toEn(string|int|float $value): string
    {
        return str_replace(
            array_merge(self::FA, self::AR),
            array_merge(self::EN, self::EN),
            (string) $value
        );
    }

    /**
     * Format an amount with thousand separators (٬). Persian digits by default.
     */
    public static function money(int $amount, bool $fa = true): string
    {
        $formatted = number_format(abs($amount), 0, '.', '٬');

        if ($amount < 0) {
            $formatted = '−'.$formatted;
        }

        return $fa ? self::toFa($formatted) : $formatted;
    }

    /**
     * Plain integer string for <input> values (never formatted, never Persian).
     */
    public static function plain(int $value): string
    {
        return (string) $value;
    }

    /**
     * Convert user input to int: normalize digits, strip everything else.
     */
    public static function toInt(mixed $value): int
    {
        $cleaned = preg_replace('/[^\d\-]/u', '', self::toEn(trim((string) $value)));

        return $cleaned === '' || $cleaned === '-' || $cleaned === null ? 0 : (int) $cleaned;
    }
}
