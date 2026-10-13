<?php

namespace Database\Seeders\Administration;

use App\Enums\Administration\RoleCode;
use App\Models\Administration\RoleModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            RoleCode::SystemAdmin->value => [
                'role_name' => 'Administrateur système',
                'description' => 'Accès complet à l’administration, au registre, aux missions, aux statistiques, au journal et aux sauvegardes.',
            ],
            RoleCode::RegistryManager->value => [
                'role_name' => 'Responsable du registre',
                'description' => 'Déclare les formulaires, gère leurs métadonnées, affecte les missions, finalise les dossiers et consulte les statistiques.',
            ],
            RoleCode::SearchAgent->value => [
                'role_name' => 'Agent de recherche',
                'description' => 'Déclare les formulaires et traite uniquement les missions de recherche qui lui sont affectées.',
            ],
            RoleCode::ReadOnly->value => [
                'role_name' => 'Consultation',
                'description' => 'Consulte le registre et le tableau de bord sans modifier les données métier.',
            ],
        ];

        DB::transaction(function () use ($roles): void {
            foreach ($roles as $roleCode => $attributes) {
                $role = RoleModel::withTrashed()->firstOrNew([
                    'role_code' => $roleCode,
                ]);

                $role->fill($attributes + [
                    'is_system' => true,
                    'is_active' => true,
                ]);
                $role->deleted_at = null;
                $role->save();
            }
        });
    }
}
