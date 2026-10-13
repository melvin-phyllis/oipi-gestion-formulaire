<?php

namespace App\Http\Controllers\Audit;

use App\Dto\Audit\AuditFiltersDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Audit\AuditFiltersFormRequest;
use App\Services\Audit\AuditReadService;
use Illuminate\Contracts\View\View;

final class AuditController extends Controller
{
    public function __construct(private readonly AuditReadService $audit) {}

    public function ctrl_index(AuditFiltersFormRequest $request): View
    {
        return view('audit.index', $this->audit->srv_index(
            AuditFiltersDTO::fromValidated($request->validated()),
        ));
    }
}
