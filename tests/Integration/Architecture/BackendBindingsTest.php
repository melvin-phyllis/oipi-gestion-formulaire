<?php

namespace Tests\Integration\Architecture;

use App\Contracts\Administration\UserAccountServiceInterface;
use App\Contracts\Administration\UserDirectoryServiceInterface;
use App\Contracts\Audit\AuditActivityReadServiceInterface;
use App\Contracts\Audit\AuditContextProviderInterface;
use App\Contracts\Organization\OrganizationDirectoryServiceInterface;
use App\Contracts\Registry\RegistryReferenceReadServiceInterface;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

final class BackendBindingsTest extends TestCase
{
    public function test_every_repository_contract_resolves_to_its_implementation(): void
    {
        foreach (File::allFiles(app_path('Repositories/Interfaces')) as $file) {
            $relative = str_replace('/', '\\', substr($file->getRelativePathname(), 0, -4));
            $interface = 'App\\Repositories\\Interfaces\\'.$relative;
            $implementation = $this->app->make($interface);

            $this->assertInstanceOf($interface, $implementation);
            $this->assertSame(
                'App\\Repositories\\'.str_replace('Interface', '', $relative),
                $implementation::class,
            );
        }
    }

    public function test_domain_service_contracts_and_audit_context_are_registered(): void
    {
        foreach ([
            UserAccountServiceInterface::class,
            UserDirectoryServiceInterface::class,
            OrganizationDirectoryServiceInterface::class,
            RegistryReferenceReadServiceInterface::class,
            AuditActivityReadServiceInterface::class,
            AuditContextProviderInterface::class,
        ] as $contract) {
            $this->assertInstanceOf($contract, $this->app->make($contract));
        }
    }
}
