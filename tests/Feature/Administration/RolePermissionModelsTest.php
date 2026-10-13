<?php

namespace Tests\Feature\Administration;

use App\Models\Administration\PermissionModel;
use App\Models\Administration\RoleModel;
use App\Models\Administration\RolePermissionModel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RolePermissionModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_generate_public_and_business_identifiers(): void
    {
        $role = RoleModel::factory()->create();
        $permission = PermissionModel::factory()->create();
        $assignment = RolePermissionModel::factory()->create([
            'role_code_unique' => $role->role_code_unique,
            'permission_code_unique' => $permission->permission_code_unique,
        ]);

        $this->assertTrue(Str::isUuid($role->uuid));
        $this->assertTrue(Str::startsWith($role->role_code_unique, 'ROL-'));
        $this->assertTrue(Str::isUuid($permission->uuid));
        $this->assertTrue(Str::startsWith($permission->permission_code_unique, 'PER-'));
        $this->assertTrue(Str::isUuid($assignment->uuid));
        $this->assertTrue(Str::startsWith($assignment->role_permission_code_unique, 'RPE-'));
        $this->assertSame('uuid', $role->getRouteKeyName());
    }

    public function test_role_and_permission_relations_use_business_codes(): void
    {
        $role = RoleModel::factory()->create();
        $permission = PermissionModel::factory()->create();

        RolePermissionModel::factory()->create([
            'role_code_unique' => $role->role_code_unique,
            'permission_code_unique' => $permission->permission_code_unique,
        ]);

        $this->assertTrue($role->permissions->contains($permission));
        $this->assertTrue($permission->roles->contains($role));
    }

    public function test_a_role_cannot_receive_the_same_permission_twice(): void
    {
        $role = RoleModel::factory()->create();
        $permission = PermissionModel::factory()->create();
        $assignment = [
            'role_code_unique' => $role->role_code_unique,
            'permission_code_unique' => $permission->permission_code_unique,
        ];

        RolePermissionModel::factory()->create($assignment);

        $this->expectException(QueryException::class);

        RolePermissionModel::factory()->create($assignment);
    }
}
