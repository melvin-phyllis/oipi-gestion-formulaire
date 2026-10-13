<?php

namespace Tests\Feature\Administration;

use App\Enums\Administration\RoleCode;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use App\Models\Organization\DirectorateModel;
use App\Models\Organization\ServiceModel;
use Database\Seeders\Administration\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_authenticatable_risfm_user_linked_to_a_role(): void
    {
        $this->seed(RoleSeeder::class);

        $role = RoleModel::query()
            ->where('role_code', RoleCode::SearchAgent->value)
            ->firstOrFail();
        $user = UserModel::factory()->create([
            'role_code_unique' => $role->role_code_unique,
            'password' => 'Secret123!',
        ]);

        $this->assertTrue(Str::isUuid($user->uuid));
        $this->assertTrue(Str::startsWith($user->user_code_unique, 'USR-'));
        $this->assertSame('uuid', $user->getRouteKeyName());
        $this->assertTrue(Hash::check('Secret123!', $user->password));
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->must_change_password);
        $this->assertTrue($user->role->is($role));
        $this->assertSame(UserModel::class, config('auth.providers.users.model'));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_it_tracks_the_user_who_created_an_account(): void
    {
        $role = RoleModel::factory()->create();
        $creator = UserModel::factory()->create([
            'role_code_unique' => $role->role_code_unique,
        ]);
        $createdUser = UserModel::factory()->create([
            'role_code_unique' => $role->role_code_unique,
            'created_by_code_unique' => $creator->user_code_unique,
        ]);

        $this->assertTrue($createdUser->createdBy->is($creator));
        $this->assertTrue($creator->createdUsers->contains($createdUser));
    }

    public function test_it_links_a_user_to_a_service_and_its_directorate(): void
    {
        $directorate = DirectorateModel::factory()->create();
        $service = ServiceModel::factory()->create([
            'directorate_code_unique' => $directorate->directorate_code_unique,
        ]);
        $user = UserModel::factory()->create([
            'service_code_unique' => $service->service_code_unique,
        ]);

        $this->assertTrue($user->service->is($service));
        $this->assertTrue($service->users->contains($user));
        $this->assertTrue($user->directorate->is($directorate));
    }

    public function test_a_system_user_can_exist_without_an_organizational_service(): void
    {
        $user = UserModel::factory()->create([
            'service_code_unique' => null,
        ]);

        $this->assertNull($user->service);
        $this->assertNull($user->directorate);
    }

    public function test_it_rejects_an_unknown_role_reference(): void
    {
        $this->expectException(QueryException::class);

        UserModel::factory()->create([
            'role_code_unique' => 'ROL-UNKNOWN',
        ]);
    }

    public function test_it_rejects_an_unknown_service_reference(): void
    {
        $this->expectException(QueryException::class);

        UserModel::factory()->create([
            'service_code_unique' => 'SVC-UNKNOWN',
        ]);
    }
}
