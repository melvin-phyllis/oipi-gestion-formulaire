<?php

namespace App\Enums\Administration;

enum RoleCode: string
{
    case SystemAdmin = 'SYSTEM_ADMIN';
    case RegistryManager = 'REGISTRY_MANAGER';
    case SearchAgent = 'SEARCH_AGENT';
    case ReadOnly = 'READ_ONLY';
}
