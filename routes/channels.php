<?php

use App\Enums\Administration\PermissionCode;
use App\Models\Administration\UserModel;
use App\Models\Registry\MissingFormImportModel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'registry-imports.{missingFormImport}',
    function (UserModel $user, MissingFormImportModel $missingFormImport): bool {
        return $user->can(PermissionCode::RegistryImport->value)
            && $missingFormImport->created_by_user_code_unique !== null
            && hash_equals(
                $missingFormImport->created_by_user_code_unique,
                $user->user_code_unique,
            );
    },
);

Broadcast::channel(
    'user-notifications.{user}',
    function (UserModel $authenticatedUser, UserModel $user): bool {
        return $authenticatedUser->can(PermissionCode::NotificationsView->value)
            && hash_equals($user->uuid, $authenticatedUser->uuid);
    },
);
