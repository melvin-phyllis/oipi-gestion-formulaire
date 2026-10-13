<?php

namespace Database\Seeders\Registry;

use App\Models\Registry\TitleTypeModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class TitleTypeSeeder extends Seeder
{
    /** @var array<int, array{string, string, int}> */
    private const TITLE_TYPES = [
        ['BRV', 'Brevet', 1],
        ['DMI', 'Dessin & Modèle Industriel', 2],
        ['IG', 'Indication Géographique', 3],
        ['MAQ', 'Marque', 4],
        ['MC', 'Marque Collective', 5],
        ['MU', 'Modèle d’Utilité', 6],
        ['NC', 'Nom Commercial', 7],
        ['OV', 'Obtention Végétale', 8],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::TITLE_TYPES as [$code, $name, $displayOrder]) {
                if (TitleTypeModel::withTrashed()->where('title_type_code', $code)->exists()) {
                    continue;
                }

                TitleTypeModel::query()->create([
                    'title_type_code' => $code,
                    'title_type_name' => $name,
                    'is_active' => true,
                    'display_order' => $displayOrder,
                ]);
            }
        });
    }
}
