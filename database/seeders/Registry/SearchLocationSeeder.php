<?php

namespace Database\Seeders\Registry;

use App\Models\Registry\SearchLocationModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class SearchLocationSeeder extends Seeder
{
    /** @var array<int, array{string, string, int}> */
    private const LOCATIONS = [
        ['ARCHIVES_CENTRALES', 'Archives centrales', 10],
        ['DIRECTION_TECHNIQUE', 'Direction Technique', 20],
        ['DIRECTION_JURIDIQUE', 'Direction Juridique', 30],
        ['SALLE_ARCHIVES', 'Salle des archives', 40],
        ['OAPI', 'OAPI', 50],
        ['SERVICE_DOCUMENTATION', 'Service Documentation', 60],
        ['BUREAU_REGIONAL', 'Bureau régional', 70],
        ['DEPOSANT', 'Déposant', 80],
        ['CABINET_CONSEIL', 'Cabinet conseil', 90],
        ['AUTRE', 'Autre', 100],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::LOCATIONS as [$code, $name, $displayOrder]) {
                if (SearchLocationModel::withTrashed()->where('location_code', $code)->exists()) {
                    continue;
                }

                SearchLocationModel::query()->create([
                    'location_code' => $code,
                    'location_name' => $name,
                    'description' => null,
                    'is_active' => true,
                    'display_order' => $displayOrder,
                ]);
            }
        });
    }
}
