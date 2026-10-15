<?php

namespace App\Http\Audit;

use App\Contracts\Audit\AuditContextProviderInterface;
use App\Dto\Audit\AuditRequestContextDTO;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class HttpAuditContextProvider implements AuditContextProviderInterface
{
    public function __construct(private readonly Container $container) {}

    public function context(): AuditRequestContextDTO
    {
        $request = $this->container->bound('request') ? $this->container->make('request') : null;
        if (! $request instanceof Request || $request->route() === null) {
            return new AuditRequestContextDTO;
        }

        return new AuditRequestContextDTO(
            routeName: $request->route()->getName(),
            httpMethod: $request->method(),
            requestPath: Str::limit('/'.$request->path(), 500, ''),
            ipAddress: $request->ip(),
            userAgent: Str::limit((string) $request->userAgent(), 1000, ''),
        );
    }
}
