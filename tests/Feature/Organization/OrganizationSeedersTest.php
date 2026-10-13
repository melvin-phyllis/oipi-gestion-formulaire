<?php

namespace Tests\Feature\Organization;

use App\Models\Organization\DirectorateModel;
use App\Models\Organization\ServiceModel;
use Database\Seeders\Organization\DirectorateSeeder;
use Database\Seeders\Organization\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrganizationSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_oipi_organization_chart(): void
    {
        $this->seed(ServiceSeeder::class);

        $this->assertDatabaseCount('org_directorate_models', 6);
        $this->assertDatabaseCount('org_service_models', 24);

        $expectedServiceCounts = [
            'DG' => 9,
            'DBIITT' => 3,
            'DMDMIDAQE' => 3,
            'DIGMCDS' => 3,
            'DSIDS' => 3,
            'DCHBC' => 3,
        ];

        foreach ($expectedServiceCounts as $code => $expectedCount) {
            $directorate = DirectorateModel::query()
                ->where('directorate_code', $code)
                ->firstOrFail();

            $this->assertSame($expectedCount, $directorate->services()->count());
        }

        $service = ServiceModel::query()
            ->where('service_code', 'SDSID')
            ->firstOrFail();

        $this->assertSame('DSIDS', $service->directorate->directorate_code);
        $this->assertSame(
            'Sous-direction du système d’information et de la documentation',
            $service->service_name,
        );
    }

    public function test_organization_seeders_are_idempotent(): void
    {
        $this->seed(ServiceSeeder::class);
        $this->seed(ServiceSeeder::class);

        $this->assertDatabaseCount('org_directorate_models', 6);
        $this->assertDatabaseCount('org_service_models', 24);
    }

    public function test_seeders_do_not_overwrite_administrator_changes(): void
    {
        $this->seed(ServiceSeeder::class);

        $directorate = DirectorateModel::query()
            ->where('directorate_code', 'DG')
            ->firstOrFail();
        $directorate->update([
            'directorate_name' => 'Direction générale personnalisée',
            'is_active' => false,
        ]);

        $service = ServiceModel::query()
            ->where('service_code', 'SCE_JURIDIQUE')
            ->firstOrFail();
        $service->update([
            'service_name' => 'Service juridique personnalisé',
            'is_active' => false,
        ]);

        $this->seed(DirectorateSeeder::class);
        $this->seed(ServiceSeeder::class);

        $this->assertDatabaseHas('org_directorate_models', [
            'directorate_code' => 'DG',
            'directorate_name' => 'Direction générale personnalisée',
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('org_service_models', [
            'service_code' => 'SCE_JURIDIQUE',
            'service_name' => 'Service juridique personnalisé',
            'is_active' => false,
        ]);
        $this->assertDatabaseCount('org_directorate_models', 6);
        $this->assertDatabaseCount('org_service_models', 24);
    }

    public function test_seeders_do_not_recreate_soft_deleted_organization_records(): void
    {
        $this->seed(ServiceSeeder::class);

        DirectorateModel::query()
            ->where('directorate_code', 'DCHBC')
            ->firstOrFail()
            ->delete();
        ServiceModel::query()
            ->where('service_code', 'SCE_JURIDIQUE')
            ->firstOrFail()
            ->delete();

        $this->seed(ServiceSeeder::class);

        $this->assertSame(6, DirectorateModel::withTrashed()->count());
        $this->assertSame(24, ServiceModel::withTrashed()->count());
        $this->assertSame(5, DirectorateModel::query()->count());
        $this->assertSame(23, ServiceModel::query()->count());
    }
}
