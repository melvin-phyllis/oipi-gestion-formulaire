<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sys_database_backup_models')) {
            if (Schema::hasTable('bkp_backup_models')) {
                throw new RuntimeException('Les deux tables de sauvegardes existent. Le renommage a été interrompu pour préserver les données.');
            }

            Schema::rename('sys_database_backup_models', 'bkp_backup_models');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bkp_backup_models')) {
            if (Schema::hasTable('sys_database_backup_models')) {
                throw new RuntimeException('La table historique de sauvegardes existe déjà. Le retour arrière a été interrompu pour préserver les données.');
            }

            Schema::rename('bkp_backup_models', 'sys_database_backup_models');
        }
    }
};
