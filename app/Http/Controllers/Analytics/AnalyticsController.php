<?php

namespace App\Http\Controllers\Analytics;

use App\Dto\Analytics\AnalyticsFiltersDTO;
use App\Events\Analytics\AnalyticsExported;
use App\Http\Controllers\Controller;
use App\Http\Requests\Analytics\AnalyticsFiltersFormRequest;
use App\Models\Administration\UserModel;
use App\Services\Analytics\AnalyticsExportService;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analytics,
        private readonly AnalyticsExportService $exports,
    ) {}

    public function ctrl_index(AnalyticsFiltersFormRequest $request): View
    {
        return view('analytics.index', $this->analytics->srv_report(
            AnalyticsFiltersDTO::fromValidated($request->validated()),
        ));
    }

    public function ctrl_dashboard(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof UserModel, 403);

        return view('dashboard', $this->analytics->srv_dashboard($user));
    }

    public function ctrl_exportExcel(AnalyticsFiltersFormRequest $request): StreamedResponse
    {
        $filters = AnalyticsFiltersDTO::fromValidated($request->validated());
        $export = $this->exports->srv_excel($filters);
        $this->dispatchExported($request, 'xlsx');

        return response()->streamDownload($export->writer, $export->filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function ctrl_exportPdf(AnalyticsFiltersFormRequest $request): Response
    {
        $filters = AnalyticsFiltersDTO::fromValidated($request->validated());
        $html = view('analytics.export-pdf', $this->exports->srv_pdfData($filters))->render();
        $export = $this->exports->srv_pdf($html);
        $this->dispatchExported($request, 'pdf');

        return response($export->content, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$export->filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function dispatchExported(AnalyticsFiltersFormRequest $request, string $format): void
    {
        $user = $request->user();
        abort_unless($user instanceof UserModel, 403);

        AnalyticsExported::dispatch($format, $user->uuid, $request->validated());
    }
}
