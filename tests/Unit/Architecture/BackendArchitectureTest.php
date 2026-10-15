<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

final class BackendArchitectureTest extends TestCase
{
    public function test_dtos_are_immutable_and_independent_of_http_and_eloquent(): void
    {
        foreach ($this->phpFiles('app/Dto') as $file) {
            $source = file_get_contents($file);
            preg_match('/namespace ([^;]+);/', $source, $namespace);
            $class = new ReflectionClass($namespace[1].'\\'.pathinfo($file, PATHINFO_FILENAME));

            $this->assertTrue($class->isReadOnly(), $file);
            $this->assertDoesNotMatchRegularExpression('/use (?:App\\\\Models|Illuminate\\\\(?:Http|Database)|Symfony\\\\Component\\\\HttpFoundation)\\\\/', $source, $file);
        }
    }

    public function test_services_do_not_construct_http_responses_or_render_views(): void
    {
        foreach ($this->phpFiles('app/Services') as $file) {
            $source = file_get_contents($file);

            $this->assertDoesNotMatchRegularExpression('/use (?:Illuminate\\\\(?:Http|View)|Symfony\\\\Component\\\\HttpFoundation)\\\\/', $source, $file);
            $this->assertDoesNotMatchRegularExpression('/(?<![a-zA-Z_])(?:response|view|redirect)\s*\(/', $source, $file);
            $this->assertDoesNotMatchRegularExpression('/->(?:loadMissing|whereHas|lockForUpdate)\s*\(/', $source, $file);
        }
    }

    public function test_services_access_foreign_domains_through_service_contracts(): void
    {
        foreach ($this->phpFiles('app/Services') as $file) {
            $source = file_get_contents($file);
            preg_match('/namespace App\\\\Services\\\\([^;\\\\]+);/', $source, $namespace);
            preg_match_all('/use App\\\\Repositories\\\\Interfaces\\\\([^\\\\]+)\\\\/', $source, $imports);

            foreach ($imports[1] as $repositoryDomain) {
                $this->assertSame($namespace[1], $repositoryDomain, $file);
            }
        }
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $files = [];
        $root = dirname(__DIR__, 3);
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory)) as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
