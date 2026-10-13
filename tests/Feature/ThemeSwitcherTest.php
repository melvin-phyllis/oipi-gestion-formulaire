<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ThemeSwitcherTest extends TestCase
{
    public function test_login_page_exposes_the_persistent_theme_switcher(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee("const storageKey = 'risfm-theme'", false)
            ->assertSee('Activer le mode sombre');
    }
}
