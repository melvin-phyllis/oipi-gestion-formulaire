<?php

namespace Tests\Feature\Security;

use Illuminate\Http\Request;
use Tests\TestCase;

final class TrustedHostsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.url', 'https://risfm.oipi.test');
        $this->app->instance('env', 'production');
    }

    protected function tearDown(): void
    {
        Request::setTrustedHosts([]);

        parent::tearDown();
    }

    public function test_configured_host_is_accepted_in_production(): void
    {
        $this->get('https://risfm.oipi.test/login')->assertOk();
    }

    public function test_forged_host_is_rejected_in_production(): void
    {
        $this->get('https://attacker.example/login')->assertStatus(400);
    }

    public function test_unconfigured_subdomain_and_suffix_are_rejected_in_production(): void
    {
        $this->get('https://evil.risfm.oipi.test/login')->assertStatus(400);
        $this->get('https://risfm.oipi.test.attacker.example/login')->assertStatus(400);
    }
}
