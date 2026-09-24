<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\PersianNumbers;
use PHPUnit\Framework\TestCase;

final class PersianNumbersTest extends TestCase
{
    public function test_converts_to_persian_digits(): void
    {
        $this->assertSame('۰۱۲۳۴۵۶۷۸۹', PersianNumbers::toFa('0123456789'));
        $this->assertSame('۱۲۸۳۴۸۶', PersianNumbers::toFa(1283486));
        $this->assertSame('قیمت ۱۲٬۵۰۰ تومان', PersianNumbers::toFa('قیمت 12٬500 تومان'));
    }

    public function test_converts_to_latin_digits(): void
    {
        $this->assertSame('0123456789', PersianNumbers::toEn('۰۱۲۳۴۵۶۷۸۹'));
        // Arabic-Indic digits normalize too.
        $this->assertSame('0123', PersianNumbers::toEn('٠١٢٣'));
        // Only digits convert; separators stay untouched.
        $this->assertSame('12٬500', PersianNumbers::toEn('۱۲٬۵۰۰'));
    }

    public function test_formats_money(): void
    {
        $this->assertSame('۱٬۲۸۳٬۴۰۰', PersianNumbers::money(1283400));
        $this->assertSame('1٬283٬400', PersianNumbers::money(1283400, false));
        $this->assertSame('−۵۰۰', PersianNumbers::money(-500));
        $this->assertSame('۰', PersianNumbers::money(0));
    }

    public function test_converts_user_input_to_int(): void
    {
        $this->assertSame(12500, PersianNumbers::toInt('۱۲٬۵۰۰ تومان'));
        $this->assertSame(42, PersianNumbers::toInt('  42 '));
        $this->assertSame(-12, PersianNumbers::toInt('-۱۲'));
        $this->assertSame(0, PersianNumbers::toInt(''));
        $this->assertSame(0, PersianNumbers::toInt('-'));
        $this->assertSame(0, PersianNumbers::toInt('abc'));
    }

    public function test_plain_returns_unformatted_string(): void
    {
        $this->assertSame('42', PersianNumbers::plain(42));
    }
}
