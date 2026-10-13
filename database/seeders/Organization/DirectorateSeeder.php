<?php

namespace Database\Seeders\Organization;

use App\Models\Organization\DirectorateModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DirectorateSeeder extends Seeder
{
    /** @var array<int, array{string, string, int}> */
    private const DIRECTORATES = [
        ['DG', 'Direction générale (DG)', 10],
        ['DBIITT', 'Direction des brevets d’invention, de l’innovation et du transfert de technologie (DBIITT)', 20],
        ['DMDMIDAQE', 'Direction des marques, des DMI, des droits d’auteurs et des questions émergentes (DMDMIDAQE)', 30],
        ['DIGMCDS', 'Direction des indications géographiques, des marques collectives et du développement des services (DIGMCDS)', 40],
        ['DSIDS', 'Direction du système d’information, de la documentation et des statistiques (DSIDS)', 50],
        ['DCHBC', 'Direction du capital humain, du budget et de la comptabilité (DCHBC)', 60],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::DIRECTORATES as [$code, $name, $displayOrder]) {
                if (DirectorateModel::withTrashed()->where('directorate_code', $code)->exists()) {
                    continue;
                }

                DirectorateModel::query()->create([
                    'directorate_code' => $code,
                    'directorate_name' => $name,
                    'is_active' => true,
                    'display_order' => $displayOrder,
                ]);
            }
        });
    }
}
