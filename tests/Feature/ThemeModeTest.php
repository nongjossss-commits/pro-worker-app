<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Light / dark / device display mode — see partials/_theme_init,
 * partials/_theme_switcher and public/css/theme-dark.css.
 */
class ThemeModeTest extends TestCase
{
    public function test_switcher_offers_the_three_modes(): void
    {
        $html = view('partials._theme_switcher')->render();

        foreach (['light', 'dark', 'system'] as $mode) {
            $this->assertStringContainsString('data-theme-set="' . $mode . '"', $html);
        }
        $this->assertStringContainsString(__('Device default'), $html);
    }

    public function test_login_page_applies_the_saved_mode_before_it_paints(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString("var KEY = 'pw_theme'", $html);
        $this->assertLessThan(strpos($html, '<style>'), strpos($html, "var KEY = 'pw_theme'"));
        $this->assertStringContainsString('html[data-bs-theme="dark"]', $html);
    }

    public function test_error_pages_follow_the_mode_too(): void
    {
        $html = $this->get('/this-page-does-not-exist-' . uniqid())->assertNotFound()->getContent();

        $this->assertStringContainsString("var KEY = 'pw_theme'", $html);
    }

    public function test_dark_stylesheet_only_targets_dark_mode(): void
    {
        $css = file_get_contents(public_path('css/theme-dark.css'));
        $css = preg_replace('#/\*.*?\*/#s', '', $css);

        // Every rule (outside @keyframes) must be scoped to html[data-bs-theme="dark"],
        // so light mode can never change because of this file.
        $css = preg_replace('/@keyframes[^{]+\{(?:[^{}]*\{[^}]*\})*\s*\}/', '', $css);
        preg_match_all('/([^{}]+)\{[^{}]*\}/', $css, $m);
        $this->assertNotEmpty($m[1]);
        foreach ($m[1] as $selectorList) {
            foreach ($this->topLevelSelectors($selectorList) as $selector) {
                $this->assertStringStartsWith('html[data-bs-theme="dark"]', trim($selector), 'Unscoped selector: ' . trim($selector));
            }
        }
    }

    /** Split "a, b:is(c, d:not(e, f)), g" on the top-level commas only. */
    private function topLevelSelectors(string $list): array
    {
        $parts = [];
        $depth = 0;
        $quote = null;
        $current = '';
        foreach (str_split($list) as $ch) {
            if ($quote !== null) {
                if ($ch === $quote) {
                    $quote = null;
                }
            } elseif ($ch === '"' || $ch === "'") {
                $quote = $ch;
            } elseif ($ch === '(' || $ch === '[') {
                $depth++;
            } elseif ($ch === ')' || $ch === ']') {
                $depth--;
            } elseif ($ch === ',' && $depth === 0) {
                $parts[] = $current;
                $current = '';
                continue;
            }
            $current .= $ch;
        }
        $parts[] = $current;

        return array_filter($parts, fn ($p) => trim($p) !== '');
    }
}
