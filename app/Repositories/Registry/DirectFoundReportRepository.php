<?php

namespace App\Repositories\Registry;

use App\Dto\Registry\RegisterFoundMissingFormDTO;
use App\Enums\Registry\FormStatus;
use App\Models\Administration\UserModel;
use App\Models\Registry\DirectFoundReportModel;
use App\Models\Registry\MissingFormModel;
use App\Models\Registry\SearchLocationModel;
use App\Repositories\Interfaces\Registry\DirectFoundReportRepositoryInterface;

final class DirectFoundReportRepository implements DirectFoundReportRepositoryInterface
{
    public function repo_findActiveFinder(string $userCodeUnique): ?UserModel
    {
        return UserModel::query()
            ->with(['service.directorate'])
            ->where('user_code_unique', $userCodeUnique)
            ->where('is_active', true)
            ->first();
    }

    public function repo_findActiveLocation(string $locationCodeUnique): ?SearchLocationModel
    {
        return SearchLocationModel::query()
            ->where('search_location_code_unique', $locationCodeUnique)
            ->where('is_active', true)
            ->first();
    }

    public function repo_createReport(
        MissingFormModel $form,
        RegisterFoundMissingFormDTO $data,
        ?UserModel $finder,
        UserModel $actor,
    ): DirectFoundReportModel {
        $finderName = $finder
            ? trim($finder->last_name.' '.$finder->first_names)
            : (string) $data->finderName;
        $finderOrganization = $finder
            ? $finder->service?->service_name
            : $data->finderOrganization;

        return DirectFoundReportModel::query()->create([
            'missing_form_code_unique' => $form->missing_form_code_unique,
            'tracking_cycle' => $form->tracking_cycle,
            'finder_type' => $data->finderType,
            'finder_user_code_unique' => $finder?->user_code_unique,
            'finder_name' => $finderName,
            'finder_organization' => $finderOrganization,
            'finder_contact' => $finder ? null : $data->finderContact,
            'found_location_code_unique' => $data->foundLocationCodeUnique,
            'found_on' => $data->foundOn,
            'result_summary' => $data->resultSummary,
            'recorded_by_user_code_unique' => $actor->user_code_unique,
            'recorded_by_name' => trim($actor->last_name.' '.$actor->first_names),
        ]);
    }

    public function repo_markFormFound(
        MissingFormModel $form,
        RegisterFoundMissingFormDTO $data,
        UserModel $actor,
    ): MissingFormModel {
        $form->update([
            'status' => FormStatus::Found,
            'last_search_location_code_unique' => $data->foundLocationCodeUnique,
            'last_responsible_user_code_unique' => null,
            'search_due_date' => null,
            'last_search_date' => $data->foundOn,
            'resolved_at' => $data->foundOn,
            'result_summary' => $data->resultSummary,
            'updated_by_user_code_unique' => $actor->user_code_unique,
        ]);

        return $form->refresh();
    }
}
