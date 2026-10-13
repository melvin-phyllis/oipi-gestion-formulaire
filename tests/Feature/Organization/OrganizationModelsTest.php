<?php

namespace Tests\Feature\Organization;

use App\Models\Organization\DirectorateModel;
use App\Models\Organization\ServiceModel;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class OrganizationModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_generate_public_and_business_identifiers(): void
    {
        $directorate = DirectorateModel::factory()->create();
        $service = ServiceModel::factory()->create([
            'directorate_code_unique' => $directorate->directorate_code_unique,
        ]);

        $this->assertTrue(Str::isUuid($directorate->uuid));
        $this->assertTrue(Str::startsWith($directorate->directorate_code_unique, 'DIR-'));
        $this->assertTrue(Str::isUuid($service->uuid));
        $this->assertTrue(Str::startsWith($service->service_code_unique, 'SVC-'));
        $this->assertSame('uuid', $directorate->getRouteKeyName());
        $this->assertSame('uuid', $service->getRouteKeyName());
    }

    public function test_a_directorate_groups_its_services_through_business_codes(): void
    {
        $directorate = DirectorateModel::factory()->create();
        $service = ServiceModel::factory()->create([
            'directorate_code_unique' => $directorate->directorate_code_unique,
        ]);

        $this->assertTrue($directorate->services->contains($service));
        $this->assertTrue($service->directorate->is($directorate));
        $this->assertSame(
            $directorate->directorate_code_unique,
            $service->directorate_code_unique,
        );
    }

    public function test_a_service_name_cannot_be_duplicated_inside_the_same_directorate(): void
    {
        $directorate = DirectorateModel::factory()->create();
        $attributes = [
            'directorate_code_unique' => $directorate->directorate_code_unique,
            'service_name' => 'Service de test',
        ];

        ServiceModel::factory()->create($attributes);

        $this->expectException(QueryException::class);

        ServiceModel::factory()->create($attributes);
    }

    public function test_the_same_service_name_can_exist_in_two_directorates(): void
    {
        $firstDirectorate = DirectorateModel::factory()->create();
        $secondDirectorate = DirectorateModel::factory()->create();

        ServiceModel::factory()->create([
            'directorate_code_unique' => $firstDirectorate->directorate_code_unique,
            'service_name' => 'Bureau du directeur',
        ]);
        ServiceModel::factory()->create([
            'directorate_code_unique' => $secondDirectorate->directorate_code_unique,
            'service_name' => 'Bureau du directeur',
        ]);

        $this->assertDatabaseCount('org_service_models', 2);
    }
}
