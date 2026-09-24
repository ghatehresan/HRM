<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Anti-drift guard: every color in design-tokens.json MUST appear in the
 * Tailwind theme (resources/css/app.css). If the brand book changes a value,
 * update the token file AND the theme together — this test fails otherwise.
 */
final class BrandTokensTest extends TestCase
{
    public function test_theme_css_contains_every_token_color(): void
    {
        $tokensPath = __DIR__.'/../../resources/tokens/design-tokens.json';
        $cssPath = __DIR__.'/../../resources/css/app.css';

        $this->assertFileExists($tokensPath);
        $this->assertFileExists($cssPath);

        /** @var array{color: array<string, array{value: string, role: string}>} $tokens */
        $tokens = json_decode((string) file_get_contents($tokensPath), true);
        $this->assertIsArray($tokens);

        $css = (string) file_get_contents($cssPath);

        $missing = [];
        foreach ($tokens['color'] as $name => $definition) {
            if (! str_contains($css, (string) $definition['value'])) {
                $missing[] = "{$name} ({$definition['value']})";
            }
        }

        $this->assertSame([], $missing, 'Token colors missing from app.css @theme.');
    }

    public function test_orange_usage_rule_is_documented(): void
    {
        // The 60/30/10 rule cannot be asserted in code, but the token file
        // MUST document the 10% cap so reviewers keep enforcing it.
        $tokensPath = __DIR__.'/../../resources/tokens/design-tokens.json';

        /** @var array{color: array<string, array{value: string, role: string}>} $tokens */
        $tokens = json_decode((string) file_get_contents($tokensPath), true);

        $this->assertStringContainsString('10%', $tokens['color']['orange']['role']);
    }
}
