<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Statistiques et pilotage du RISFM</title>
    <style>
        @page { margin: 24px; }
        body { color: #182433; font-family: "DejaVu Sans", sans-serif; font-size: 9px; }
        h1 { color: #008e4f; font-size: 20px; margin: 0 0 3px; }
        h2 { border-bottom: 2px solid #008e4f; color: #17352a; font-size: 13px; margin: 18px 0 7px; padding-bottom: 4px; }
        .muted { color: #667382; }
        .filters { background: #f2f7f4; border: 1px solid #d8e5de; padding: 8px; }
        .filters span { display: inline-block; margin: 2px 14px 2px 0; }
        .kpis { width: 100%; border-collapse: separate; border-spacing: 6px; margin: 10px 0; }
        .kpis td { background: #f5f8f6; border: 1px solid #d8e5de; padding: 8px; width: 25%; }
        .kpis strong { color: #008e4f; display: block; font-size: 18px; margin-top: 4px; }
        table.data { border-collapse: collapse; width: 100%; }
        table.data th { background: #008e4f; color: #fff; padding: 5px; text-align: left; }
        table.data td { border-bottom: 1px solid #dfe5e2; padding: 5px; }
        table.data .number { text-align: right; }
        .columns { width: 100%; border-collapse: separate; border-spacing: 8px 0; }
        .columns > tbody > tr > td { vertical-align: top; width: 50%; }
        .page-break { page-break-before: always; }
        .footer { color: #667382; font-size: 8px; margin-top: 14px; text-align: right; }
    </style>
</head>
<body>
    <h1>Statistiques et pilotage du RISFM</h1>
    <div class="muted">Office Ivoirien de la Propriété Intellectuelle — généré le {{ $generatedAt->format('d/m/Y à H:i') }}</div>

    <h2>Filtres appliqués</h2>
    <div class="filters">
        @foreach ($appliedFilters as $label => $value)
            <span><strong>{{ $label }} :</strong> {{ $value }}</span>
        @endforeach
    </div>

    <table class="kpis">
        <tr>
            <td>Campagne active<strong>{{ number_format($kpis['activeCampaignForms'], 0, ',', ' ') }}</strong></td>
            <td>Taux de résolution<strong>{{ $kpis['resolutionRate'] }} %</strong></td>
            <td>Taux de saisie ERP<strong>{{ $kpis['completionRate'] }} %</strong></td>
            <td>Missions actives<strong>{{ number_format($kpis['activeMissions'], 0, ',', ' ') }}</strong></td>
        </tr>
    </table>

    <table class="columns">
        <tr>
            <td>
                <h2>Répartition par statut</h2>
                <table class="data">
                    <thead><tr><th>Statut</th><th class="number">Nombre</th><th class="number">%</th></tr></thead>
                    <tbody>
                        @foreach ($statusDistribution as $item)
                            <tr><td>{{ $item['label'] }}</td><td class="number">{{ $item['total'] }}</td><td class="number">{{ $item['percentage'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
            <td>
                <h2>Répartition par type de titre</h2>
                <table class="data">
                    <thead><tr><th>Type</th><th class="number">Nombre</th><th class="number">%</th></tr></thead>
                    <tbody>
                        @forelse ($titleTypeDistribution as $item)
                            <tr><td>{{ $item['label'] }} ({{ $item['code'] }})</td><td class="number">{{ $item['total'] }}</td><td class="number">{{ $item['percentage'] }}</td></tr>
                        @empty
                            <tr><td colspan="3">Aucune donnée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <div class="page-break"></div>
    <table class="columns">
        <tr>
            <td>
                <h2>Performance par localisation</h2>
                <table class="data">
                    <thead><tr><th>Localisation</th><th class="number">Recherches</th><th class="number">Retrouvés</th><th class="number">%</th></tr></thead>
                    <tbody>
                        @forelse ($locationPerformance as $item)
                            <tr><td>{{ $item['label'] }}</td><td class="number">{{ $item['searches'] }}</td><td class="number">{{ $item['found'] }}</td><td class="number">{{ $item['successRate'] }}</td></tr>
                        @empty
                            <tr><td colspan="4">Aucune donnée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td>
                <h2>Performance par responsable</h2>
                <table class="data">
                    <thead><tr><th>Responsable</th><th class="number">Recherches</th><th class="number">Retrouvés</th><th class="number">À vérifier</th><th class="number">%</th></tr></thead>
                    <tbody>
                        @forelse ($responsiblePerformance as $item)
                            <tr><td>{{ $item['label'] }}</td><td class="number">{{ $item['searches'] }}</td><td class="number">{{ $item['found'] }}</td><td class="number">{{ $item['needsVerification'] }}</td><td class="number">{{ $item['successRate'] }}</td></tr>
                        @empty
                            <tr><td colspan="5">Aucune donnée.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <h2>Évolution mensuelle</h2>
    <table class="data">
        <thead><tr><th>Mois</th><th class="number">Recherches</th><th class="number">Signalements retrouvés</th><th class="number">Dossiers résolus</th></tr></thead>
        <tbody>
            @foreach ($monthlyTrend as $item)
                <tr><td>{{ $item['label'] }}</td><td class="number">{{ $item['searches'] }}</td><td class="number">{{ $item['foundReports'] }}</td><td class="number">{{ $item['resolvedForms'] }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">Document généré automatiquement par le RISFM.</div>
</body>
</html>
