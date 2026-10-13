<?php

namespace App\Http\Controllers\Mission;

use App\Dto\Mission\AssignSearchMissionDTO;
use App\Dto\Mission\CancelSearchMissionDTO;
use App\Dto\Mission\MissionFiltersDTO;
use App\Dto\Mission\ReassignSearchMissionDTO;
use App\Dto\Mission\RecordMissionResultDTO;
use App\Dto\Mission\ReviewFoundReportDTO;
use App\Dto\Mission\StartSearchMissionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mission\AssignSearchMissionFormRequest;
use App\Http\Requests\Mission\CancelSearchMissionFormRequest;
use App\Http\Requests\Mission\ConfirmFoundReportFormRequest;
use App\Http\Requests\Mission\MissionFiltersFormRequest;
use App\Http\Requests\Mission\ReassignSearchMissionFormRequest;
use App\Http\Requests\Mission\RecordMissionResultFormRequest;
use App\Http\Requests\Mission\RejectFoundReportFormRequest;
use App\Http\Requests\Mission\StartSearchMissionFormRequest;
use App\Http\Requests\Mission\ViewMissionFormRequest;
use App\Models\Mission\SearchMissionModel;
use App\Models\Registry\MissingFormModel;
use App\Services\Mission\FoundReportReviewService;
use App\Services\Mission\SearchMissionReadService;
use App\Services\Mission\SearchMissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class SearchMissionController extends Controller
{
    public function __construct(
        private readonly SearchMissionService $missions,
        private readonly SearchMissionReadService $missionReader,
        private readonly FoundReportReviewService $foundReports,
    ) {}

    public function ctrl_index(MissionFiltersFormRequest $request): View
    {
        return view('missions.index', $this->missionReader->srv_list(
            MissionFiltersDTO::fromValidated($request->validated()),
            $request->user(),
        ));
    }

    public function ctrl_show(
        ViewMissionFormRequest $request,
        SearchMissionModel $searchMission,
    ): View {
        return view('missions.show', $this->missionReader->srv_showData(
            $searchMission,
            $request->user(),
        ));
    }

    public function ctrl_assign(
        AssignSearchMissionFormRequest $request,
        MissingFormModel $missingForm,
    ): RedirectResponse {
        $this->missions->srv_assign(
            $missingForm,
            AssignSearchMissionDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return back()->with('success', 'La mission de recherche a été affectée.');
    }

    public function ctrl_recordResult(
        RecordMissionResultFormRequest $request,
        SearchMissionModel $searchMission,
    ): RedirectResponse {
        $this->missions->srv_recordResult(
            $searchMission,
            RecordMissionResultDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return back()->with('success', 'Le compte rendu de recherche a été enregistré.');
    }

    public function ctrl_start(
        StartSearchMissionFormRequest $request,
        SearchMissionModel $searchMission,
    ): RedirectResponse {
        $this->missions->srv_start(
            $searchMission,
            StartSearchMissionDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return back()->with('success', 'La mission de recherche a été démarrée.');
    }

    public function ctrl_cancel(
        CancelSearchMissionFormRequest $request,
        SearchMissionModel $searchMission,
    ): RedirectResponse {
        $this->missions->srv_cancel(
            $searchMission,
            CancelSearchMissionDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return back()->with('success', 'La mission de recherche a été annulée.');
    }

    public function ctrl_reassign(
        ReassignSearchMissionFormRequest $request,
        SearchMissionModel $searchMission,
    ): RedirectResponse {
        $this->missions->srv_reassign(
            $searchMission,
            ReassignSearchMissionDTO::fromValidated($request->validated()),
            $request->user(),
        );

        return back()->with('success', 'La mission de recherche a été réaffectée.');
    }

    public function ctrl_confirmFound(
        ConfirmFoundReportFormRequest $request,
        SearchMissionModel $searchMission,
    ): RedirectResponse {
        $this->foundReports->srv_confirm(
            $searchMission,
            ReviewFoundReportDTO::confirmed(),
            $request->user(),
        );

        return back()->with('success', 'Le signalement a été confirmé. Le formulaire est maintenant retrouvé.');
    }

    public function ctrl_rejectFound(
        RejectFoundReportFormRequest $request,
        SearchMissionModel $searchMission,
    ): RedirectResponse {
        $this->foundReports->srv_reject(
            $searchMission,
            ReviewFoundReportDTO::rejected($request->validated()),
            $request->user(),
        );

        return back()->with('success', 'Le signalement a été refusé et la recherche peut continuer.');
    }
}
