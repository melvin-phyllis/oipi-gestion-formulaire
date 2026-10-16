<?php

namespace App\Http\Controllers\Administration;

use App\Dto\Administration\NotificationFiltersDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\ManageNotificationFormRequest;
use App\Http\Requests\Administration\NotificationFiltersFormRequest;
use App\Services\Administration\UserNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

final class NotificationController extends Controller
{
    public function __construct(
        private readonly UserNotificationService $notifications,
    ) {}

    public function ctrl_index(NotificationFiltersFormRequest $request): View
    {
        return view('notifications.index', $this->notifications->srv_list(
            NotificationFiltersDTO::fromValidated($request->validated()),
            $request->user(),
        ));
    }

    public function ctrl_data(
        ManageNotificationFormRequest $request,
        string $notification,
    ): JsonResponse {
        return response()->json([
            'data' => $this->notifications->srv_notificationData($notification, $request->user()),
        ]);
    }

    public function ctrl_markRead(
        ManageNotificationFormRequest $request,
        string $notification,): RedirectResponse {
        $this->notifications->srv_markRead($notification, $request->user());

        return back()->with('success', 'La notification a été marquée comme lue.');
    }

    public function ctrl_markAllRead(ManageNotificationFormRequest $request): RedirectResponse
    {
        $count = $this->notifications->srv_markAllRead($request->user());

        return back()->with(
            'success',
            $count > 0
                ? 'Toutes les notifications ont été marquées comme lues.'
                : 'Aucune notification non lue.',
        );
    }
}
