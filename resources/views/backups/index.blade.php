@extends('layouts.app')

@section('title', 'Sauvegardes de la base de données')

@section('content')
    <!-- EN-TETE DE PAGE -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center g-2">
            <div class="col">
                <div class="page-pretitle text-uppercase text-primary fw-semibold">Administration & Maintenance</div>
                <h1 class="page-title d-flex align-items-center gap-2">
                    <span>Sauvegardes de la base de données</span>
                    <span class="badge bg-primary-lt text-primary fs-6 font-monospace" id="backup-total-badge">
                        Chargement...
                    </span>
                </h1>
                <div class="text-secondary mt-1">
                    Gestion des instantanés SQL, création manuelle, téléchargement sécurisé et restauration sous haute surveillance.
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" id="btn-refresh" title="Actualiser la liste">
                    <i class="ti ti-refresh me-1"></i>Actualiser
                </button>
                <button type="button" class="btn btn-primary" id="btn-create-backup">
                    <i class="ti ti-database-plus me-1"></i>Créer une sauvegarde manuelle
                </button>
            </div>
        </div>
    </div>

    <!-- ZONE DE MESSAGES ET ALERTES DYNAMIQUES -->
    <div id="alerts-container"></div>

    <!-- BANDEAU DE POLITIQUE DE RETENTION ET SECURITE -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex align-items-start gap-3">
                
                <div class="flex-fill">
                    <div class="fw-semibold text-dark mb-1">Politique de sauvegarde et haute intégrité</div>
                    <div class="text-secondary small">
                        Les sauvegardes automatiques sont conservées <strong>14 jours</strong> avant rétention programmée. Les sauvegardes manuelles sont conservées sans limite d’expiration.
                        Avant chaque restauration, une <strong>sauvegarde de sécurité immédiate</strong> est automatiquement générée pour garantir le retour arrière en cas d'anomalie.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CARTE DES FILTRES -->
    <div class="card mb-3">
        <div class="card-header py-2 d-flex align-items-center justify-content-between">
            <h3 class="card-title fs-5 mb-0">
                <i class="ti ti-filter me-2 text-primary"></i>
                Filtrer les sauvegardes
            </h3>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-reset-filters">
                <i class="ti ti-x me-1"></i>Réinitialiser
            </button>
        </div>
        <div class="card-body py-3">
            <form id="filter-form" onsubmit="event.preventDefault(); window.loadBackups(1);">
                <div class="row g-3 align-items-end">
                    <!-- TYPE DE SAUVEGARDE -->
                    <div class="col-12 col-md-4">
                        <label class="form-label mb-1" for="filter-type">Type de sauvegarde</label>
                        <select class="form-select" id="filter-type">
                            <option value="">Tous les types</option>
                            <option value="manual">Manuelle</option>
                            <option value="scheduled">Automatique</option>
                            <option value="pre_restore">Sécurité avant restauration</option>
                        </select>
                    </div>

                    <!-- DATE DEBUT -->
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label mb-1" for="filter-created-from">Période du</label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-calendar text-secondary"></i></span>
                            <input type="date" class="form-control" id="filter-created-from">
                        </div>
                    </div>

                    <!-- DATE FIN -->
                    <div class="col-12 col-sm-6 col-md-3">
                        <label class="form-label mb-1" for="filter-created-to">Au</label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-calendar text-secondary"></i></span>
                            <input type="date" class="form-control" id="filter-created-to">
                        </div>
                    </div>

                    <!-- BOUTON FILTRER -->
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100" id="btn-apply-filters">
                            <i class="ti ti-check me-1"></i>Filtrer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLEAU DES SAUVEGARDES -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover">
                <thead>
                    <tr>
                        <th style="min-width: 140px;">Code unique</th>
                        <th style="min-width: 180px;">Type</th>
                        <th style="min-width: 220px;">Nom de fichier</th>
                        <th style="min-width: 110px;">Taille</th>
                        <th style="min-width: 160px;">Créateur</th>
                        <th style="min-width: 160px;">Date de création</th>
                        <th class="w-1 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="backups-tbody">
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status"></div>
                            <div class="text-secondary mt-2 small">Chargement des sauvegardes...</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- PIED DE CARTE / PAGINATION -->
        <div class="card-footer d-flex flex-wrap align-items-center justify-content-between py-2 gap-2" id="backups-footer">
            <div class="text-secondary small" id="pagination-info">
                —
            </div>
            <ul class="pagination m-0" id="pagination-links"></ul>
        </div>
    </div>
@endsection

@push('modals')
    <!-- MODALE DE CONFIRMATION DE CREATION MANUELLE -->
    <div class="modal modal-blur fade" id="modal-confirm-create" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="ti ti-database-plus text-primary fs-3"></i>
                        <span>Créer une sauvegarde manuelle</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark">
                        Vous êtes sur le point de déclencher un instantané complet de la base de données du système RISFM.
                    </p>
                    <div class="p-3 rounded border bg-light small mb-0 text-secondary">
                        <i class="ti ti-shield-check text-primary me-1"></i>
                        Cette sauvegarde sera horodatée, compressée en <code>.sql.gz</code> et archivée dans l'espace sécurisé.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="btn-submit-create">
                        <i class="ti ti-check me-1"></i>Lancer la sauvegarde
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALE DE RESTAURATION HAUTE SECURITE -->
    <div class="modal modal-blur fade" id="modal-restore" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger-lt border-bottom">
                    <h5 class="modal-title text-danger d-flex align-items-center gap-2">
                        <i class="ti ti-alert-triangle fs-2"></i>
                        <span>Restauration de la base de données · Haute sécurité</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer" id="btn-close-restore-top"></button>
                </div>
                <div class="modal-body">
                    <!-- AVERTISSEMENT CRITIQUE -->
                    <div class="alert alert-danger d-flex align-items-start gap-2 mb-3">
                        <i class="ti ti-alert-octagon fs-2 text-danger mt-1"></i>
                        <div>
                            <div class="fw-bold mb-1">Opération critique à impact immédiat</div>
                            <div class="small">
                                La restauration écrase l'ensemble des données opérationnelles actuelles par l'instantané sélectionné.
                                L'application sera temporairement <strong>mise en maintenance</strong> pendant l'opération.
                                Une sauvegarde de sécurité sera automatiquement enregistrée juste avant l'application du fichier.
                            </div>
                        </div>
                    </div>

                    <!-- DETAIL DE LA SAUVEGARDE CIBLE -->
                    <div class="card mb-3 bg-light border">
                        <div class="card-body py-2">
                            <div class="row g-2 small">
                                <div class="col-sm-6">
                                    <span class="text-secondary">Code sauvegarde :</span>
                                    <span class="fw-semibold font-monospace text-dark ms-1" id="restore-target-code">—</span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-secondary">Fichier :</span>
                                    <span class="fw-semibold font-monospace text-dark ms-1" id="restore-target-file">—</span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-secondary">Créée le :</span>
                                    <span class="fw-semibold text-dark ms-1" id="restore-target-date">—</span>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-secondary">Taille archive :</span>
                                    <span class="fw-semibold text-dark ms-1" id="restore-target-size">—</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ZONE DE MESSAGE D'ERREUR DU MODAL -->
                    <div id="restore-modal-alert"></div>

                    <!-- SAISIE OBLIGATOIRE DE LA PHRASE DE CONFIRMATION -->
                    <div class="mb-3">
                        <label class="form-label required" for="restore-phrase-input">
                            1. Phrase de confirmation obligatoire
                        </label>
                        <div class="text-secondary small mb-2">
                            Pour déverrouiller la restauration, saisissez au clavier exactement la phrase suivante en majuscules (<strong>le copier-coller est strictement interdit</strong>) :
                            <div class="mt-1">
                                <span class="badge bg-danger text-light font-monospace fs-5 px-3 py-1">RESTAURER OIPI</span>
                            </div>
                        </div>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-keyboard text-secondary"></i></span>
                            <input
                                type="text"
                                class="form-control font-monospace"
                                id="restore-phrase-input"
                                placeholder="Tapez ici : RESTAURER OIPI"
                                autocomplete="off"
                                spellcheck="false"
                            >
                        </div>
                        <div class="invalid-feedback d-block small mt-1 text-danger d-none" id="restore-phrase-hint">
                            La phrase saisie ne correspond pas exactement à « RESTAURER OIPI ».
                        </div>
                    </div>

                    <!-- SAISIE DU MOT DE PASSE ACTUEL -->
                    <div class="mb-3">
                        <label class="form-label required" for="restore-password-input">
                            2. Mot de passe de votre compte administrateur
                        </label>
                        <div class="text-secondary small mb-1">
                            Confirmez votre identité avec le mot de passe actuel de votre session.
                        </div>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-lock text-secondary"></i></span>
                            <input
                                type="password"
                                class="form-control"
                                id="restore-password-input"
                                placeholder="Votre mot de passe actuel"
                                autocomplete="current-password"
                            >
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="btn-cancel-restore">
                        Annuler
                    </button>
                    <button type="button" class="btn btn-danger" id="btn-submit-restore" disabled>
                        <i class="ti ti-history me-1"></i>Confirmer la restauration
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALE DE SUPPRESSION D'UNE SAUVEGARDE -->
    <div class="modal modal-blur fade" id="modal-delete" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger d-flex align-items-center gap-2">
                        <i class="ti ti-trash fs-3"></i>
                        <span>Supprimer la sauvegarde</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark mb-2">
                        Êtes-vous sûr de vouloir supprimer définitivement la sauvegarde suivante ?
                    </p>
                    <div class="bg-light p-3 rounded small mb-3">
                        <div><strong>Code :</strong> <span id="delete-target-code" class="font-monospace text-dark"></span></div>
                        <div><strong>Fichier :</strong> <span id="delete-target-file" class="font-monospace text-secondary"></span></div>
                    </div>
                    <div class="alert alert-warning small mb-0">
                        <i class="ti ti-alert-triangle me-1"></i>
                        Cette action est irréversible. L'archive <code>.sql.gz</code> sera supprimée du disque sécurisé.
                    </div>
                    <div id="delete-modal-alert" class="mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="btn-submit-delete">
                        <i class="ti ti-trash me-1"></i>Supprimer définitivement
                    </button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const alertsContainer = document.getElementById('alerts-container');
    const tbody = document.getElementById('backups-tbody');
    const badgeTotal = document.getElementById('backup-total-badge');
    const paginationInfo = document.getElementById('pagination-info');
    const paginationLinks = document.getElementById('pagination-links');

    let currentPage = 1;
    let selectedRestoreUuid = null;
    let selectedDeleteUuid = null;

    // Helper: Formatter la taille en Ko / Mo
    function formatBytes(bytes) {
        if (!bytes || bytes <= 0) return '0 o';
        const k = 1024;
        const sizes = ['o', 'Ko', 'Mo', 'Go', 'To'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Helper: Date lisible
    function formatDate(isoStr) {
        if (!isoStr) return '—';
        const d = new Date(isoStr);
        return d.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        }) + ' ' + d.toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    }

    // Helper: Afficher une alerte globale
    function showAlert(message, type = 'success', details = null) {
        alertsContainer.innerHTML = `
            <div class="alert alert-${type} alert-dismissible d-flex align-items-center mb-3" role="alert">
                <i class="ti ti-${type === 'success' ? 'circle-check' : (type === 'danger' ? 'alert-circle' : 'info-circle')} fs-2 me-2"></i>
                <div class="flex-fill">
                    <div>${message}</div>
                    ${details ? `<div class="small mt-1 opacity-75">${details}</div>` : ''}
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        `;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Helper: Badge de type
    function getTypeBadge(type) {
        switch (type) {
            case 'manual':
                return '<span class="badge bg-green-lt text-green"><i class="ti ti-user-check me-1"></i>Manuelle</span>';
            case 'scheduled':
                return '<span class="badge bg-secondary-lt text-secondary"><i class="ti ti-clock me-1"></i>Automatique</span>';
            case 'pre_restore':
                return '<span class="badge bg-warning-lt text-warning"><i class="ti ti-shield-lock me-1"></i>Sécurité avant restauration</span>';
            default:
                return `<span class="badge bg-light text-secondary">${type}</span>`;
        }
    }

    // Charger les sauvegardes via GET /backups/data
    window.loadBackups = async function (page = 1) {
        currentPage = page;
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="text-secondary mt-2 small">Chargement des sauvegardes...</div>
                </td>
            </tr>
        `;

        const type = document.getElementById('filter-type').value;
        const createdFrom = document.getElementById('filter-created-from').value;
        const createdTo = document.getElementById('filter-created-to').value;

        const params = new URLSearchParams();
        params.append('page', page);
        if (type) params.append('type', type);
        if (createdFrom) params.append('created_from', createdFrom);
        if (createdTo) params.append('created_to', createdTo);

        try {
            const response = await fetch(`{{ route('backups.data') }}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                if (response.status === 403) {
                    throw new Error("Accès refusé : vous n'avez pas la permission de gérer les sauvegardes.");
                }
                throw new Error(`Erreur serveur (${response.status}) lors du chargement des données.`);
            }

            const result = await response.json();
            renderTable(result.data, result.meta);
        } catch (error) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-danger">
                        <i class="ti ti-alert-triangle fs-2 d-block mb-2"></i>
                        ${error.message || 'Impossible de récupérer la liste des sauvegardes.'}
                    </td>
                </tr>
            `;
            badgeTotal.textContent = 'Erreur';
        }
    };

    // Rendu du tableau
    function renderTable(backups, meta) {
        badgeTotal.textContent = `${meta.total || 0} sauvegarde(s)`;

        if (!backups || backups.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="empty">
                            <div class="empty-icon">
                                <i class="ti ti-database-off fs-1 text-secondary"></i>
                            </div>
                            <p class="empty-title fs-3 fw-bold">Aucune sauvegarde enregistrée</p>
                            <p class="empty-subtitle text-secondary">
                                Aucun instantané ne correspond aux critères de recherche actuels.
                            </p>
                        </div>
                    </td>
                </tr>
            `;
            paginationInfo.textContent = '0 sauvegarde';
            paginationLinks.innerHTML = '';
            return;
        }

        let html = '';
        backups.forEach(backup => {
            const downloadUrl = `{{ url('backups') }}/${backup.uuid}/download`;

            html += `
                <tr>
                    <!-- CODE UNIQUE -->
                    <td class="text-nowrap">
                        <span class="badge bg-light text-dark font-monospace fw-bold">
                            <i class="ti ti-tag me-1 text-primary"></i>${backup.code}
                        </span>
                    </td>

                    <!-- TYPE -->
                    <td>${getTypeBadge(backup.type)}</td>

                    <!-- NOM DE FICHIER -->
                    <td>
                        <span class="font-monospace text-dark small" title="${backup.file_name}">
                            ${backup.file_name}
                        </span>
                    </td>

                    <!-- TAILLE -->
                    <td class="text-nowrap font-monospace text-secondary small">
                        ${formatBytes(backup.size_bytes)}
                    </td>

                    <!-- CREATEUR -->
                    <td>
                        <span class="text-dark small fw-medium">
                            ${backup.creator_name ? backup.creator_name : '<span class="text-secondary fst-italic">Système</span>'}
                        </span>
                    </td>

                    <!-- DATE DE CREATION -->
                    <td class="text-nowrap">
                        <span class="text-dark small">${formatDate(backup.created_at)}</span>
                    </td>

                    <!-- ACTIONS -->
                    <td class="text-center text-nowrap">
                        <div class="d-flex align-items-center justify-content-center gap-1">
                            <!-- TELECHARGER -->
                            <a href="${downloadUrl}" class="btn btn-sm btn-outline-secondary" title="Télécharger l'archive">
                                <i class="ti ti-download me-1"></i>Télécharger
                            </a>

                            <!-- RESTAURER -->
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-warning btn-open-restore"
                                data-uuid="${backup.uuid}"
                                data-code="${backup.code}"
                                data-file="${backup.file_name}"
                                data-date="${formatDate(backup.created_at)}"
                                data-size="${formatBytes(backup.size_bytes)}"
                                title="Restaurer cette version de la base de données"
                            >
                                <i class="ti ti-history me-1"></i>Restaurer
                            </button>

                            <!-- SUPPRIMER -->
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger btn-open-delete"
                                data-uuid="${backup.uuid}"
                                data-code="${backup.code}"
                                data-file="${backup.file_name}"
                                title="Supprimer la sauvegarde"
                            >
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;

        // Metadonnées de pagination
        const from = (meta.current_page - 1) * meta.per_page + 1;
        const to = Math.min(meta.current_page * meta.per_page, meta.total);
        paginationInfo.innerHTML = `Affichage de <strong>${from}</strong> à <strong>${to}</strong> sur <strong>${meta.total}</strong> sauvegardes`;

        renderPagination(meta);
        attachRowEventListeners();
    }

    // Rendu des liens de pagination
    function renderPagination(meta) {
        if (meta.last_page <= 1) {
            paginationLinks.innerHTML = '';
            return;
        }

        let linksHtml = '';

        // Bouton Précédent
        linksHtml += `
            <li class="page-item ${meta.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${meta.current_page - 1}" tabindex="-1" aria-disabled="${meta.current_page === 1}">
                    <i class="ti ti-chevron-left"></i>
                </a>
            </li>
        `;

        for (let p = 1; p <= meta.last_page; p++) {
            if (p === 1 || p === meta.last_page || (p >= meta.current_page - 2 && p <= meta.current_page + 2)) {
                linksHtml += `
                    <li class="page-item ${p === meta.current_page ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${p}">${p}</a>
                    </li>
                `;
            } else if (p === meta.current_page - 3 || p === meta.current_page + 3) {
                linksHtml += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
            }
        }

        // Bouton Suivant
        linksHtml += `
            <li class="page-item ${meta.current_page === meta.last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${meta.current_page + 1}">
                    <i class="ti ti-chevron-right"></i>
                </a>
            </li>
        `;

        paginationLinks.innerHTML = linksHtml;

        paginationLinks.querySelectorAll('a.page-link').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const p = parseInt(this.getAttribute('data-page'));
                if (p && p !== currentPage && p >= 1 && p <= meta.last_page) {
                    window.loadBackups(p);
                }
            });
        });
    }

    // Attacher les écouteurs pour les boutons de chaque ligne
    function attachRowEventListeners() {
        // Boutons Restaurer
        document.querySelectorAll('.btn-open-restore').forEach(btn => {
            btn.addEventListener('click', function () {
                selectedRestoreUuid = this.getAttribute('data-uuid');
                document.getElementById('restore-target-code').textContent = this.getAttribute('data-code');
                document.getElementById('restore-target-file').textContent = this.getAttribute('data-file');
                document.getElementById('restore-target-date').textContent = this.getAttribute('data-date');
                document.getElementById('restore-target-size').textContent = this.getAttribute('data-size');

                // Reset formulaire
                const phraseInput = document.getElementById('restore-phrase-input');
                const passwordInput = document.getElementById('restore-password-input');
                phraseInput.value = '';
                passwordInput.value = '';
                document.getElementById('restore-phrase-hint').classList.add('d-none');
                document.getElementById('restore-modal-alert').innerHTML = '';
                document.getElementById('btn-submit-restore').disabled = true;

                const modal = new window.tabler.Modal(document.getElementById('modal-restore'));
                modal.show();
            });
        });

        // Boutons Supprimer
        document.querySelectorAll('.btn-open-delete').forEach(btn => {
            btn.addEventListener('click', function () {
                selectedDeleteUuid = this.getAttribute('data-uuid');
                document.getElementById('delete-target-code').textContent = this.getAttribute('data-code');
                document.getElementById('delete-target-file').textContent = this.getAttribute('data-file');
                document.getElementById('delete-modal-alert').innerHTML = '';

                const modal = new window.tabler.Modal(document.getElementById('modal-delete'));
                modal.show();
            });
        });
    }

    // Gestion de la création manuelle
    const btnCreateTrigger = document.getElementById('btn-create-backup');
    btnCreateTrigger.addEventListener('click', function () {
        const modal = new window.tabler.Modal(document.getElementById('modal-confirm-create'));
        modal.show();
    });

    const btnSubmitCreate = document.getElementById('btn-submit-create');
    btnSubmitCreate.addEventListener('click', async function () {
        btnSubmitCreate.classList.add('btn-loading');
        btnSubmitCreate.disabled = true;

        try {
            const response = await fetch(`{{ route('backups.store') }}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Impossible de créer la sauvegarde.');
            }

            // Fermer modale
            window.tabler.Modal.getInstance(document.getElementById('modal-confirm-create'))?.hide();
            showAlert(`La sauvegarde manuelle a été créée avec succès (${result.data.code}).`, 'success');
            window.loadBackups(1);
        } catch (error) {
            showAlert(error.message || 'Une erreur est survenue lors de la création de la sauvegarde.', 'danger');
            window.tabler.Modal.getInstance(document.getElementById('modal-confirm-create'))?.hide();
        } finally {
            btnSubmitCreate.classList.remove('btn-loading');
            btnSubmitCreate.disabled = false;
        }
    });

    // Protection anti copier-coller stricte sur le champ de confirmation
    const restorePhraseInput = document.getElementById('restore-phrase-input');
    const restorePasswordInput = document.getElementById('restore-password-input');
    const btnSubmitRestore = document.getElementById('btn-submit-restore');
    const phraseHint = document.getElementById('restore-phrase-hint');

    ['paste', 'drop'].forEach(eventType => {
        restorePhraseInput.addEventListener(eventType, function (e) {
            e.preventDefault();
            phraseHint.textContent = 'Le copier-coller est désactivé. Veuillez taper la phrase manuellement au clavier.';
            phraseHint.classList.remove('d-none');
        });
    });

    // Validation dynamique pour activer le bouton de restauration
    function validateRestoreInputs() {
        const phrase = restorePhraseInput.value.trim();
        const password = restorePasswordInput.value.trim();
        const isPhraseValid = (phrase === 'RESTAURER OIPI');

        if (phrase.length > 0 && !isPhraseValid) {
            phraseHint.textContent = 'La phrase saisie ne correspond pas exactement à « RESTAURER OIPI ».';
            phraseHint.classList.remove('d-none');
        } else {
            phraseHint.classList.add('d-none');
        }

        btnSubmitRestore.disabled = !(isPhraseValid && password.length > 0);
    }

    restorePhraseInput.addEventListener('input', validateRestoreInputs);
    restorePasswordInput.addEventListener('input', validateRestoreInputs);

    // Soumission de la restauration
    btnSubmitRestore.addEventListener('click', async function () {
        if (!selectedRestoreUuid) return;

        btnSubmitRestore.classList.add('btn-loading');
        btnSubmitRestore.disabled = true;
        document.getElementById('btn-cancel-restore').disabled = true;
        document.getElementById('btn-close-restore-top').disabled = true;
        restorePhraseInput.disabled = true;
        restorePasswordInput.disabled = true;

        const modalAlert = document.getElementById('restore-modal-alert');
        modalAlert.innerHTML = `
            <div class="alert alert-warning d-flex align-items-center mb-3">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                <div>Restauration en cours... La base de données est mise en maintenance. Veuillez patienter sans fermer la page.</div>
            </div>
        `;

        try {
            const url = `{{ url('backups') }}/${selectedRestoreUuid}/restore`;
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    confirmation_phrase: restorePhraseInput.value.trim(),
                    current_password: restorePasswordInput.value
                })
            });

            const result = await response.json();

            if (!response.ok) {
                let errorMsg = result.message || 'Échec de la restauration de la base de données.';
                if (result.errors) {
                    const firstKey = Object.keys(result.errors)[0];
                    if (result.errors[firstKey] && result.errors[firstKey][0]) {
                        errorMsg = result.errors[firstKey][0];
                    }
                }
                throw new Error(errorMsg);
            }

            // Succès
            window.tabler.Modal.getInstance(document.getElementById('modal-restore'))?.hide();

            let successDetails = '';
            if (result.safety_backup) {
                successDetails = `Une sauvegarde de sécurité préalable a été créée : <strong>${result.safety_backup.code}</strong> (${result.safety_backup.file_name}).`;
            }

            showAlert(result.message || 'La base de données a été restaurée avec succès.', 'success', successDetails);
            window.loadBackups(1);
        } catch (error) {
            modalAlert.innerHTML = `
                <div class="alert alert-danger d-flex align-items-center mb-3">
                    <i class="ti ti-alert-circle fs-3 me-2"></i>
                    <div>${error.message}</div>
                </div>
            `;
        } finally {
            btnSubmitRestore.classList.remove('btn-loading');
            document.getElementById('btn-cancel-restore').disabled = false;
            document.getElementById('btn-close-restore-top').disabled = false;
            restorePhraseInput.disabled = false;
            restorePasswordInput.disabled = false;
            validateRestoreInputs();
        }
    });

    // Soumission de la suppression
    const btnSubmitDelete = document.getElementById('btn-submit-delete');
    btnSubmitDelete.addEventListener('click', async function () {
        if (!selectedDeleteUuid) return;

        btnSubmitDelete.classList.add('btn-loading');
        btnSubmitDelete.disabled = true;

        try {
            const url = `{{ url('backups') }}/${selectedDeleteUuid}`;
            const response = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Impossible de supprimer la sauvegarde.');
            }

            window.tabler.Modal.getInstance(document.getElementById('modal-delete'))?.hide();
            showAlert(result.message || 'La sauvegarde a été supprimée.', 'success');
            window.loadBackups(currentPage);
        } catch (error) {
            document.getElementById('delete-modal-alert').innerHTML = `
                <div class="alert alert-danger small mb-0">${error.message}</div>
            `;
        } finally {
            btnSubmitDelete.classList.remove('btn-loading');
            btnSubmitDelete.disabled = false;
        }
    });

    // Réinitialisation des filtres
    document.getElementById('btn-reset-filters').addEventListener('click', function () {
        document.getElementById('filter-type').value = '';
        document.getElementById('filter-created-from').value = '';
        document.getElementById('filter-created-to').value = '';
        window.loadBackups(1);
    });

    // Bouton rafraîchir
    document.getElementById('btn-refresh').addEventListener('click', function () {
        window.loadBackups(currentPage);
    });

    // Premier chargement automatique
    window.loadBackups(1);
});
</script>
@endpush
