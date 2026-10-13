@extends('layouts.app')

@section('title', 'Gestion des rôles et permissions')

@section('content')
    @php
        $totalPermissionsCount = $permissionGroups->flatten()->count();
        $groupIcons = [
            'dashboard' => 'ti ti-home',
            'registry' => 'ti ti-file-search',
            'missions' => 'ti ti-target',
            'attachments' => 'ti ti-paperclip',
            'analytics' => 'ti ti-chart-bar',
            'administration' => 'ti ti-settings',
            'audit' => 'ti ti-history',
            'backups' => 'ti ti-database',
            'notifications' => 'ti ti-bell',
            'profile' => 'ti ti-user',
        ];

        $defaultActiveUuid = old('role_uuid') ?? $roles->first()?->uuid;
    @endphp

    <!-- EN-TETE DE PAGE -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <div class="page-pretitle text-uppercase text-primary fw-semibold">Administration du système</div>
                <h1 class="page-title d-flex align-items-center gap-2">
                    <i class="ti ti-shield-lock text-primary"></i>
                    <span>Rôles & Permissions</span>
                </h1>
                <div class="text-secondary mt-1">
                    Sélectionnez un rôle via les onglets pour consulter ou ajuster ses permissions métier.
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a class="btn btn-outline-secondary" href="{{ route('users.index') }}">
                    <i class="ti ti-users me-1"></i>Gestion des utilisateurs
                </a>
            </div>
        </div>
    </div>

    <!-- CARTE PRINCIPALE AVEC NAVIGATION PAR ONGLETS (TABS) -->
    <div class="card">
        <!-- BARRE D'ONGLETS PAR ROLE -->
        <div class="card-header border-bottom py-0 px-3 bg-light-subtle">
            <ul class="nav nav-tabs card-header-tabs" id="role-tabs-list" role="tablist">
                @foreach ($roles as $role)
                    @php
                        $isSystemAdmin = ($role->role_code === \App\Enums\Administration\RoleCode::SystemAdmin->value);
                        $isActiveTab = ($role->uuid === $defaultActiveUuid);
                        $activeCount = $isSystemAdmin
                            ? $totalPermissionsCount
                            : $role->permissions->pluck('permission_code')->count();
                    @endphp
                    <li class="nav-item" role="presentation">
                        <a
                            href="#role-{{ $role->uuid }}"
                            class="nav-link py-3 px-3 d-flex align-items-center gap-2 {{ $isActiveTab ? 'active' : '' }}"
                            id="tab-btn-{{ $role->uuid }}"
                            data-bs-toggle="tab"
                            data-bs-target="#role-{{ $role->uuid }}"
                            role="tab"
                            aria-controls="role-{{ $role->uuid }}"
                            aria-selected="{{ $isActiveTab ? 'true' : 'false' }}"
                        >
                            @if ($isSystemAdmin)
                                <i class="ti ti-lock text-danger"></i>
                            @else
                                <i class="ti ti-shield text-primary"></i>
                            @endif
                            <span class="fw-semibold">{{ $role->role_name }}</span>

                            @if ($isSystemAdmin)
                                <span class="badge bg-danger-lt text-danger ms-1 font-monospace" style="font-size: 0.72rem;">
                                    {{ $totalPermissionsCount }} / {{ $totalPermissionsCount }}
                                </span>
                            @else
                                <span class="badge bg-green-lt text-green ms-1 font-monospace" id="nav-count-{{ $role->uuid }}" style="font-size: 0.72rem;">
                                    {{ $activeCount }} / {{ $totalPermissionsCount }}
                                </span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- CONTENU DES ONGLETS -->
        <div class="tab-content" id="role-tabs-content">
            @foreach ($roles as $role)
                @php
                    $isSystemAdmin = ($role->role_code === \App\Enums\Administration\RoleCode::SystemAdmin->value);
                    $isActiveTab = ($role->uuid === $defaultActiveUuid);
                    $isOldSubmitted = (old('role_uuid') === $role->uuid);

                    if ($isSystemAdmin) {
                        $assignedPermissionCodes = $permissionGroups->flatten()->pluck('permission_code');
                    } elseif ($isOldSubmitted && old('permissions') !== null) {
                        $rawOld = old('permissions', []);
                        $assignedPermissionCodes = collect(is_array($rawOld) ? array_filter($rawOld) : []);
                    } else {
                        $assignedPermissionCodes = $role->permissions->pluck('permission_code');
                    }

                    $activeCount = $assignedPermissionCodes->count();
                @endphp

                <div
                    class="tab-pane fade {{ $isActiveTab ? 'show active' : '' }}"
                    id="role-{{ $role->uuid }}"
                    role="tabpanel"
                    aria-labelledby="tab-btn-{{ $role->uuid }}"
                >
                    <!-- BANDEAU RECAPITULATIF DU ROLE ACTIF -->
                    <div class="p-3 border-bottom bg-light">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div class="d-flex align-items-center gap-3">
                                
                                <div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        
                                        @if ($isSystemAdmin)
                                            <span class="badge bg-danger-lt text-danger">
                                                <i class="ti ti-lock me-1"></i>Protégé (Lecture seule)
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-secondary small mt-1">
                                        {{ $role->description }}
                                    </div>
                                </div>
                            </div>

                          
                        </div>
                    </div>

                    @if ($isSystemAdmin)
                        <!-- VUE EN LECTURE SEULE POUR SYSTEM_ADMIN -->
                        <div class="card-body py-3">
                            <div class="p-3 rounded border bg-light d-flex align-items-start gap-2 mb-4">
                                <i class="ti ti-shield-check text-primary fs-2 mt-1"></i>
                                <div>
                                    <div class="fw-bold text-dark">Rôle d'administration système protégé</div>
                                    <div class="small text-secondary">
                                        L'administrateur système détient statutairement l'intégralité des privilèges de la plateforme. Ses permissions sont actives par défaut et verrouillées en écriture.
                                    </div>
                                </div>
                            </div>

                            <!-- GRILLE DES PERMISSIONS EN LECTURE SEULE -->
                            <div class="row g-3">
                                @foreach ($permissionGroups as $groupCode => $groupPermissions)
                                    <div class="col-12 col-md-6 col-xl-4">
                                        <div class="card card-sm h-100 border shadow-none bg-body-tertiary">
                                            <div class="card-header bg-light-subtle py-2 px-3 d-flex justify-content-between align-items-center">
                                                <span class="fw-bold fs-4 d-flex align-items-center gap-2">
                                                    <i class="{{ $groupIcons[$groupCode] ?? 'ti ti-folder' }} text-primary"></i>
                                                    <span>{{ $groupLabels[$groupCode] ?? ucfirst($groupCode) }}</span>
                                                </span>
                                                <span class="badge bg-secondary-lt font-monospace text-secondary" style="font-size: 0.72rem;">
                                                    {{ $groupPermissions->count() }}
                                                </span>
                                            </div>
                                            <div class="card-body p-2 d-flex flex-column gap-1">
                                                @foreach ($groupPermissions as $permission)
                                                    <label class="form-check form-switch m-0 p-2 rounded bg-light" for="perm-{{ $role->uuid }}-{{ $permission->permission_code }}">
                                                        <input
                                                            type="checkbox"
                                                            class="form-check-input"
                                                            id="perm-{{ $role->uuid }}-{{ $permission->permission_code }}"
                                                            checked
                                                            disabled
                                                        >
                                                        <span class="form-check-label d-block ms-1">
                                                            <span class="fw-semibold text-dark d-block">{{ $permission->permission_name }}</span>
                                                            @if ($permission->description)
                                                                <span class="text-secondary small d-block lh-sm mb-1">{{ $permission->description }}</span>
                                                            @endif
                                                            <code class="text-muted small font-monospace" style="font-size: 0.72rem;">{{ $permission->permission_code }}</code>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <!-- FORMULAIRE DE MISE A JOUR POUR LES AUTRES ROLES -->
                        <form method="post" action="{{ route('roles.permissions.update', $role) }}" id="form-role-{{ $role->uuid }}">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="role_uuid" value="{{ $role->uuid }}">
                            <input type="hidden" name="permissions[]" value="">

                            <div class="card-body py-3">
                                <!-- ALERTE ERREUR SI PRESENT -->
                                @if ($isOldSubmitted && $errors->any())
                                    <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
                                        <i class="ti ti-alert-triangle fs-2 mt-1"></i>
                                        <div>
                                            <div class="fw-bold">Erreur lors de la mise à jour des permissions :</div>
                                            <ul class="mb-0 ps-3 small">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endif

                               

                                <!-- GRILLE DES PERMISSIONS PAR GROUPE -->
                                <div class="row g-3">
                                    @foreach ($permissionGroups as $groupCode => $groupPermissions)
                                        <div class="col-12 col-md-6 col-xl-4">
                                            <div class="card card-sm h-100 border shadow-none bg-body-tertiary">
                                                <div class="card-header bg-light-subtle py-2 px-3 d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold fs-4 d-flex align-items-center gap-2">
                                                        <i class="{{ $groupIcons[$groupCode] ?? 'ti ti-folder' }} text-primary"></i>
                                                        <span>{{ $groupLabels[$groupCode] ?? ucfirst($groupCode) }}</span>
                                                    </span>
                                                    <span class="badge bg-secondary-lt font-monospace text-secondary" style="font-size: 0.72rem;">
                                                        {{ $groupPermissions->count() }}
                                                    </span>
                                                </div>
                                                <div class="card-body p-2 d-flex flex-column gap-1">
                                                    @foreach ($groupPermissions as $permission)
                                                        @php
                                                            $isChecked = $assignedPermissionCodes->contains($permission->permission_code);
                                                            $checkboxId = 'perm-' . $role->uuid . '-' . $permission->permission_code;
                                                        @endphp
                                                        <label class="form-check form-switch m-0 p-2 rounded {{ $isChecked ? 'bg-light' : '' }}" for="{{ $checkboxId }}">
                                                            <input
                                                                type="checkbox"
                                                                class="form-check-input role-perm-checkbox-{{ $role->uuid }}"
                                                                name="permissions[]"
                                                                value="{{ $permission->permission_code }}"
                                                                id="{{ $checkboxId }}"
                                                                @checked($isChecked)
                                                                onchange="toggleCheckVisual(this, '{{ $role->uuid }}')"
                                                            >
                                                            <span class="form-check-label d-block ms-1">
                                                                <span class="fw-semibold text-dark d-block">{{ $permission->permission_name }}</span>
                                                                @if ($permission->description)
                                                                    <span class="text-secondary small d-block lh-sm mb-1">{{ $permission->description }}</span>
                                                                @endif
                                                                <code class="text-muted small font-monospace" style="font-size: 0.72rem;">{{ $permission->permission_code }}</code>
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- FOOTER DE L'ONGLET AVEC CONFIRMATION D'ENREGISTREMENT -->
                            <div class="card-footer d-flex flex-wrap justify-content-between align-items-center py-2 px-3 gap-2 bg-light">
                                <div class="d-flex align-items-center gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        onclick="checkAllRolePermissions('{{ $role->uuid }}', true)"
                                    >
                                        <i class="ti ti-checks me-1"></i>Tout cocher
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        onclick="checkAllRolePermissions('{{ $role->uuid }}', false)"
                                    >
                                        <i class="ti ti-square me-1"></i>Tout décocher
                                    </button>
                                </div>
                                <button type="submit" class="btn btn-primary" id="btn-save-{{ $role->uuid }}">
                                    <i class="ti ti-device-floppy me-1"></i>Enregistrer les permissions
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Activation automatique de l'onglet selon le hash URL (ex. #role-uuid)
            function activateTabFromHash() {
                const hash = window.location.hash;
                if (hash && hash.startsWith('#role-')) {
                    const targetTabBtn = document.querySelector('a[data-bs-toggle="tab"][href="' + hash + '"]');
                    if (targetTabBtn && window.bootstrap && window.bootstrap.Tab) {
                        const tab = new window.bootstrap.Tab(targetTabBtn);
                        tab.show();
                    }
                }
            }

            activateTabFromHash();
            window.addEventListener('hashchange', activateTabFromHash);

            // Synchronisation de l'URL hash lors du clic sur un onglet
            const tabLinks = document.querySelectorAll('a[data-bs-toggle="tab"]');
            tabLinks.forEach(function (link) {
                link.addEventListener('shown.bs.tab', function (e) {
                    const targetHref = e.target.getAttribute('href');
                    if (targetHref && history.replaceState) {
                        history.replaceState(null, null, targetHref);
                    }
                });
            });
        });

        function toggleCheckVisual(input, roleUuid) {
            const label = input.closest('.form-check');
            if (label) {
                if (input.checked) {
                    label.classList.add('bg-light');
                } else {
                    label.classList.remove('bg-light');
                }
            }
            updateRoleCount(roleUuid);
        }

        function updateRoleCount(roleUuid) {
            const checked = document.querySelectorAll('.role-perm-checkbox-' + roleUuid + ':checked').length;
            const countEl = document.getElementById('active-count-' + roleUuid);
            if (countEl) {
                countEl.textContent = checked;
            }
            const navCountEl = document.getElementById('nav-count-' + roleUuid);
            if (navCountEl) {
                const total = {{ $totalPermissionsCount }};
                navCountEl.textContent = checked + ' / ' + total;
            }
        }

        function checkAllRolePermissions(roleUuid, check) {
            const checkboxes = document.querySelectorAll('.role-perm-checkbox-' + roleUuid);
            checkboxes.forEach(function (cb) {
                if (!cb.disabled) {
                    cb.checked = check;
                    const label = cb.closest('.form-check');
                    if (label) {
                        if (check) {
                            label.classList.add('bg-light');
                        } else {
                            label.classList.remove('bg-light');
                        }
                    }
                }
            });
            updateRoleCount(roleUuid);
        }
    </script>
@endpush
