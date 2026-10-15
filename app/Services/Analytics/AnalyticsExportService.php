<?php

namespace App\Services\Analytics;

use App\Dto\Analytics\AnalyticsFiltersDTO;
use App\Dto\Analytics\PdfExportDTO;
use App\Dto\Analytics\SpreadsheetExportDTO;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class AnalyticsExportService
{
    private const HEADER_COLOR = '008E4F';

    public function __construct(private readonly AnalyticsService $analytics) {}

    public function srv_excel(AnalyticsFiltersDTO $filters): SpreadsheetExportDTO
    {
        $report = $this->analytics->srv_report($filters);
        $spreadsheet = $this->buildSpreadsheet($report, $filters);

        return new SpreadsheetExportDTO(
            $this->filename('xlsx'),
            function () use ($spreadsheet): void {
                try {
                    (new Xlsx($spreadsheet))->save('php://output');
                } finally {
                    $spreadsheet->disconnectWorksheets();
                }
            },
        );
    }

    /** @return array<string, mixed> */
    public function srv_pdfData(AnalyticsFiltersDTO $filters): array
    {
        $report = $this->analytics->srv_report($filters);

        return [
            ...$report,
            'appliedFilters' => $this->appliedFilters($report, $filters),
            'generatedAt' => now(),
        ];
    }

    public function srv_pdf(string $html): PdfExportDTO
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('a4', 'landscape');
        $pdf->render();

        return new PdfExportDTO($this->filename('pdf'), $pdf->output());
    }

    /** @param array<string, mixed> $report */
    private function buildSpreadsheet(array $report, AnalyticsFiltersDTO $filters): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator('OIPI - RISFM')
            ->setTitle('Statistiques et pilotage du RISFM')
            ->setDescription('Export filtré des indicateurs du registre des formulaires manquants.');

        $summary = $spreadsheet->getActiveSheet();
        $summary->setTitle('Synthèse');
        $this->writeTitle($summary, 'Statistiques et pilotage du RISFM');
        $summary->setCellValueExplicit('A3', 'Généré le', DataType::TYPE_STRING);
        $summary->setCellValueExplicit('B3', now()->format('d/m/Y H:i'), DataType::TYPE_STRING);

        $row = 5;
        $summary->setCellValueExplicit("A{$row}", 'Filtres appliqués', DataType::TYPE_STRING);
        $this->styleSectionTitle($summary, "A{$row}:B{$row}");
        $row++;
        foreach ($this->appliedFilters($report, $filters) as $label => $value) {
            $this->setSafeValue($summary, "A{$row}", $label);
            $this->setSafeValue($summary, "B{$row}", $value);
            $row++;
        }

        $row += 2;
        $this->writeTable($summary, $row, ['Indicateur', 'Valeur'], [
            ['Total des formulaires', $report['kpis']['totalForms']],
            ['Campagne active', $report['kpis']['activeCampaignForms']],
            ['Formulaires archivés', $report['kpis']['archivedForms']],
            ['Formulaires résolus', $report['kpis']['resolvedForms']],
            ['Formulaires saisis', $report['kpis']['enteredForms']],
            ['Taux de résolution (%)', $report['kpis']['resolutionRate']],
            ['Taux de saisie (%)', $report['kpis']['completionRate']],
            ['Missions totales', $report['kpis']['totalMissions']],
            ['Missions actives', $report['kpis']['activeMissions']],
            ['Missions en retard', $report['kpis']['overdueMissions']],
            ['Missions terminées', $report['kpis']['completedMissions']],
        ]);

        $this->writeCollectionSheet(
            $spreadsheet,
            'Statuts',
            ['Statut', 'Nombre', 'Pourcentage (%)'],
            $report['statusDistribution']->map(fn (array $item): array => [
                $item['label'], $item['total'], $item['percentage'],
            ]),
        );
        $this->writeCollectionSheet(
            $spreadsheet,
            'Types de titres',
            ['Code', 'Type de titre', 'Nombre', 'Pourcentage (%)'],
            $report['titleTypeDistribution']->map(fn (array $item): array => [
                $item['code'], $item['label'], $item['total'], $item['percentage'],
            ]),
        );
        $this->writeCollectionSheet(
            $spreadsheet,
            'Localisations',
            ['Localisation', 'Recherches', 'Retrouvés', 'Taux de succès (%)'],
            $report['locationPerformance']->map(fn (array $item): array => [
                $item['label'], $item['searches'], $item['found'], $item['successRate'],
            ]),
        );
        $this->writeCollectionSheet(
            $spreadsheet,
            'Responsables',
            ['Responsable', 'Recherches', 'Retrouvés', 'Introuvables', 'À vérifier', 'Taux de succès (%)'],
            $report['responsiblePerformance']->map(fn (array $item): array => [
                $item['label'],
                $item['searches'],
                $item['found'],
                $item['notFound'],
                $item['needsVerification'],
                $item['successRate'],
            ]),
        );
        $this->writeCollectionSheet(
            $spreadsheet,
            'Évolution mensuelle',
            ['Mois', 'Recherches', 'Signalements retrouvés', 'Dossiers résolus'],
            $report['monthlyTrend']->map(fn (array $item): array => [
                $item['label'], $item['searches'], $item['foundReports'], $item['resolvedForms'],
            ]),
        );
        $this->writeCollectionSheet(
            $spreadsheet,
            'Années des titres',
            ['Année du titre', 'Nombre de dossiers'],
            $report['yearDistribution']->map(fn (array $item): array => [
                $item['year'], $item['total'],
            ]),
        );

        $summary->getColumnDimension('A')->setWidth(30);
        $summary->getColumnDimension('B')->setWidth(34);
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param  Collection<int, array<int, int|float|string|null>>  $rows
     * @param  array<int, string>  $headers
     */
    private function writeCollectionSheet(
        Spreadsheet $spreadsheet,
        string $title,
        array $headers,
        Collection $rows,
    ): void {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle($title);
        $this->writeTitle($sheet, $title);
        $this->writeTable($sheet, 3, $headers, $rows->all());
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, int|float|string|null>>  $rows
     */
    private function writeTable(Worksheet $sheet, int $startRow, array $headers, array $rows): void
    {
        foreach ($headers as $column => $header) {
            $this->setSafeValue($sheet, Coordinate::stringFromColumnIndex($column + 1).$startRow, $header);
        }

        $lastColumn = Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = "A{$startRow}:{$lastColumn}{$startRow}";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::HEADER_COLOR]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->setAutoFilter($headerRange);
        $sheet->freezePane('A'.($startRow + 1));

        foreach ($rows as $rowIndex => $values) {
            foreach ($values as $column => $value) {
                $this->setSafeValue(
                    $sheet,
                    Coordinate::stringFromColumnIndex($column + 1).($startRow + $rowIndex + 1),
                    $value,
                );
            }
        }

        foreach (range(1, count($headers)) as $column) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }
    }

    private function writeTitle(Worksheet $sheet, string $title): void
    {
        $this->setSafeValue($sheet, 'A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB(self::HEADER_COLOR);
    }

    private function styleSectionTitle(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::HEADER_COLOR]],
        ]);
    }

    private function setSafeValue(Worksheet $sheet, string $cell, int|float|string|null $value): void
    {
        if (is_int($value) || is_float($value)) {
            $sheet->setCellValue($cell, $value);

            return;
        }

        $sheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, string>
     */
    private function appliedFilters(array $report, AnalyticsFiltersDTO $filters): array
    {
        $titleType = $report['titleTypes']->firstWhere(
            'title_type_code_unique',
            $filters->titleTypeCodeUnique,
        );
        $location = $report['searchLocations']->firstWhere(
            'search_location_code_unique',
            $filters->searchLocationCodeUnique,
        );
        $responsible = $report['responsibleUsers']->firstWhere(
            'user_code_unique',
            $filters->responsibleUserCodeUnique,
        );

        return [
            'Période d’enregistrement' => $filters->registeredFrom || $filters->registeredTo
                ? ($filters->registeredFrom?->format('d/m/Y') ?? 'Début').' au '.($filters->registeredTo?->format('d/m/Y') ?? 'Aujourd’hui')
                : 'Toutes les dates',
            'Type de titre' => $titleType
                ? "{$titleType->title_type_name} ({$titleType->title_type_code})"
                : 'Tous',
            'Année du titre' => $filters->formYear ? (string) $filters->formYear : 'Toutes',
            'Statut' => $filters->status?->label() ?? 'Tous',
            'Priorité' => $filters->priority?->label() ?? 'Toutes',
            'Localisation' => $location?->location_name ?? 'Toutes',
            'Responsable' => $responsible
                ? trim("{$responsible->last_name} {$responsible->first_names}")
                : 'Tous',
            'Évolution mensuelle' => "{$filters->trendMonths} mois",
        ];
    }

    private function filename(string $extension): string
    {
        return 'risfm_statistiques_'.now()->format('Ymd_His').".{$extension}";
    }
}
