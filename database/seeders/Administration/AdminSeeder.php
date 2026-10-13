<?php

namespace Database\Seeders\Administration;

use App\Enums\Administration\RoleCode;
use App\Mail\Administration\InitialAdminCredentialsMail;
use App\Models\Administration\RoleModel;
use App\Models\Administration\UserModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

final class AdminSeeder extends Seeder
{
    private const USERNAME = 'admin';

    private const LAST_NAME = 'Administrateur';

    private const FIRST_NAMES = 'Système';

    private const EMAIL = 'melvin.akou@oipi.ci';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $existingAdmin = UserModel::withTrashed()
            ->where('username', self::USERNAME)
            ->orWhere('email', self::EMAIL)
            ->first();

        if ($existingAdmin instanceof UserModel) {
            if ($existingAdmin->trashed()) {
                throw new RuntimeException('Le compte admin initial existe mais est supprimé; restaurez-le manuellement avant le seeding.');
            }

            if ($existingAdmin->role?->role_code !== RoleCode::SystemAdmin->value) {
                throw new RuntimeException('L’identifiant ou l’adresse admin initiale est déjà utilisé par un autre compte.');
            }

            $this->command?->warn('Administrateur initial déjà présent; aucun compte ni mot de passe modifié.');

            return;
        }

        $role = RoleModel::query()
            ->where('role_code', RoleCode::SystemAdmin->value)
            ->first();

        if (! $role instanceof RoleModel) {
            throw new RuntimeException('Le rôle SYSTEM_ADMIN doit être créé avant le premier administrateur.');
        }

        $temporaryPassword = Str::password(32);

        DB::transaction(function () use ($role, $temporaryPassword): void {
            $admin = new UserModel;
            $admin->fill([
                'role_code_unique' => $role->role_code_unique,
                'username' => self::USERNAME,
                'last_name' => self::LAST_NAME,
                'first_names' => self::FIRST_NAMES,
                'email' => self::EMAIL,
                'is_active' => true,
                'must_change_password' => true,
                'session_version' => 1,
            ]);
            $admin->password = $temporaryPassword;
            $admin->save();

            // Force SMTP: the generated credential must never be written to the log mailer.
            Mail::mailer('smtp')->to(self::EMAIL)->send(
                new InitialAdminCredentialsMail(self::USERNAME, $temporaryPassword),
            );
        });

        $this->command?->info('Administrateur initial créé. Le mot de passe temporaire a été envoyé par courriel.');
    }
}
