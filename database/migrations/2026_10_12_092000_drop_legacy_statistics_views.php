<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var list<string> */
    private const LEGACY_VIEWS = [
        'vue_stats_annuelle',
        'vue_stats_mensuelle',
        'vue_stats_responsable',
        'vue_stats_statut',
        'vue_stats_type_titre',
    ];

    public function up(): void
    {
        foreach (self::LEGACY_VIEWS as $view) {
            DB::statement("DROP VIEW IF EXISTS `{$view}`");
        }
    }

    public function down(): void
    {
        // Ces vues appartenaient à l’ancienne application et dépendaient de
        // tables qui n’existent plus. Laravel calcule désormais ces données
        // via le module Analytics ; elles ne doivent donc pas être recréées.
    }
};
