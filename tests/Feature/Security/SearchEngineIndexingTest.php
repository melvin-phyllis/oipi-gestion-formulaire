<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

final class SearchEngineIndexingTest extends TestCase
{
    public function test_application_responses_prevent_search_engine_indexing(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeader(
                'X-Robots-Tag',
                'noindex, nofollow, noarchive, nosnippet',
            )
            ->assertSee(
                '<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">',
                false,
            );
    }

    public function test_robots_file_disallows_all_crawling(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertIsString($robots);
        $this->assertStringContainsString("User-agent: *\nDisallow: /", $robots);
    }
}
