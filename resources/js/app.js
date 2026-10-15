import * as tabler from '@tabler/core';
import ApexCharts from '@tabler/core/dist/libs/apexcharts/dist/apexcharts.min.js';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import './theme-switcher';

window.tabler = tabler;
window.ApexCharts = ApexCharts;

const cspNonce = document.querySelector('meta[property="csp-nonce"]')?.nonce;
if (cspNonce) {
    window.Apex = {
        ...window.Apex,
        chart: { ...window.Apex?.chart, nonce: cspNonce },
    };
}

const pusherKey = import.meta.env.VITE_PUSHER_APP_KEY;

if (pusherKey) {
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: pusherKey,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'eu',
        forceTLS: true,
        enabledTransports: ['ws', 'xhr_streaming', 'xhr_polling'],
        authEndpoint: '/broadcasting/auth',
        withCredentials: true,
    });
}

const sidebarIsFolded = () =>
    document.documentElement.getAttribute('data-bs-sidebar')?.startsWith('folded') ?? false;

document.addEventListener('change', (event) => {
    const input = event.target.closest?.('[data-auto-submit]');
    input?.form?.submit();
});

document.addEventListener('click', async (event) => {
    const button = event.target.closest?.('[data-copy-target]');
    if (!button) return;

    const target = document.getElementById(button.dataset.copyTarget);
    if (!target) return;

    try {
        await navigator.clipboard.writeText(target.innerText);
    } catch (error) {
        console.warn('La copie dans le presse-papier est indisponible.');
    }
});

const syncSidebarToggle = (folded = sidebarIsFolded()) => {
    const label = folded ? 'Agrandir la barre latérale' : 'Réduire la barre latérale';

    document.querySelectorAll('[data-risfm-sidebar-toggle]').forEach((toggle) => {
        toggle.setAttribute('aria-label', label);
        toggle.setAttribute('title', label);
        toggle.setAttribute('aria-pressed', String(folded));
    });
};

document.addEventListener('DOMContentLoaded', () => syncSidebarToggle());
document.addEventListener('tabler:sidebar-folded', (event) => {
    syncSidebarToggle(Boolean(event.detail?.folded));
});

const initializeRegistryImportProgress = () => {
    const container = document.querySelector('[data-registry-import-progress]');

    if (!container) return;

    const importUuid = container.dataset.importUuid;
    const statusUrl = container.dataset.statusUrl;
    const title = container.querySelector('[data-import-title]');
    const message = container.querySelector('[data-import-message]');
    const progressBar = container.querySelector('[data-import-progress-bar]');
    const progressLabel = container.querySelector('[data-import-progress-label]');
    const spinner = container.querySelector('[data-import-spinner]');
    const errorList = container.querySelector('[data-import-errors]');
    const channelName = `registry-imports.${importUuid}`;

    const render = (data) => {
        const progress = Math.max(0, Math.min(100, Number(data.progress) || 0));
        const processedRows = Number(data.processed_rows) || 0;
        const totalRows = Number(data.total_rows) || 0;
        const importedRows = Number(data.imported_rows) || 0;

        progressBar.style.width = `${progress}%`;
        progressBar.setAttribute('aria-valuenow', String(progress));
        progressLabel.textContent = totalRows > 0
            ? `${progress} % — ${processedRows.toLocaleString('fr-FR')} / ${totalRows.toLocaleString('fr-FR')} lignes`
            : `${progress} %`;

        if (data.status === 'completed') {
            container.classList.remove('alert-info', 'alert-danger');
            container.classList.add('alert-success');
            spinner.classList.add('d-none');
            progressBar.classList.add('bg-success');
            title.textContent = 'Import terminé';
            message.textContent = `${importedRows.toLocaleString('fr-FR')} formulaire(s) ont été importés avec succès.`;
            window.Echo?.leave(channelName);
        } else if (data.status === 'failed') {
            container.classList.remove('alert-info', 'alert-success');
            container.classList.add('alert-danger');
            spinner.classList.add('d-none');
            progressBar.classList.add('bg-danger');
            title.textContent = 'Import annulé';
            message.textContent = 'Le fichier contient des anomalies ou le traitement a échoué.';

            const errors = Array.isArray(data.errors) ? data.errors : [];
            errorList.replaceChildren(...errors.map((error) => {
                const item = document.createElement('li');
                item.textContent = error;

                return item;
            }));
            errorList.classList.toggle('d-none', errors.length === 0);
            window.Echo?.leave(channelName);
        } else if (data.status === 'processing') {
            title.textContent = 'Import en cours de validation';
            message.textContent = 'Le fichier est contrôlé avant son insertion atomique dans le registre.';
        }
    };

    const loadPrivateStatus = async () => {
        try {
            const response = await fetch(statusUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) return;

            const payload = await response.json();
            render(payload.data);
        } catch {
            // Le traitement continue côté serveur même si le navigateur est hors ligne.
        }
    };

    if (window.Echo && importUuid) {
        window.Echo
            .private(channelName)
            .listen('.registry.import.progress.updated', (data) => {
                render(data);

                if (data.status === 'failed') {
                    loadPrivateStatus();
                }
            });
    }

    loadPrivateStatus();
};

document.addEventListener('DOMContentLoaded', initializeRegistryImportProgress);

const initializeUserNotificationsRealtime = () => {
    const links = [...document.querySelectorAll('[data-user-notifications-realtime]')];

    if (links.length === 0 || !window.Echo) return;

    const link = links[0];
    const userUuid = link.dataset.userUuid;
    const liveRegions = links
        .map((notificationLink) => notificationLink.querySelector('[data-notification-live]'))
        .filter(Boolean);
    const pageCount = document.querySelector('[data-notification-page-count]');
    const tabCount = document.querySelector('[data-notification-tab-count]');
    const readAll = document.querySelector('[data-notification-read-all]');
    const realtimeAlert = document.querySelector('[data-notification-realtime-alert]');
    const notificationList = document.querySelector('[data-notifications-list]');

    if (!userUuid) return;

    const updateCount = (value) => {
        const previousCount = Math.max(...links.map((item) => Number(item.dataset.unreadCount) || 0));
        const unreadCount = Math.max(0, Number(value) || 0);
        const hasUnread = unreadCount > 0;
        const visibleCount = unreadCount > 99 ? '99+' : String(unreadCount);

        links.forEach((notificationLink) => {
            notificationLink.dataset.unreadCount = String(unreadCount);
            notificationLink.setAttribute('aria-label', hasUnread
                ? `Notifications : ${unreadCount} non lue${unreadCount > 1 ? 's' : ''}`
                : 'Notifications');

            const badge = notificationLink.querySelector('[data-notification-badge]');
            if (badge) {
                badge.textContent = visibleCount;
                badge.hidden = !hasUnread;
                badge.setAttribute('aria-label', `${unreadCount} notifications non lues`);
            }

            const bell = notificationLink.querySelector('[data-notification-bell]');
            bell?.classList.toggle('risfm-bell-shake', hasUnread);
        });

        if (unreadCount > previousCount) {
            document.querySelectorAll('[data-notification-bell]').forEach((bell) => {
                bell.classList.remove('risfm-bell-shake');
                void bell.offsetWidth;
                bell.classList.add('risfm-bell-shake');
            });
            liveRegions.forEach((liveRegion) => {
                liveRegion.textContent = `Nouvelle notification. ${unreadCount} notification${unreadCount > 1 ? 's' : ''} non lue${unreadCount > 1 ? 's' : ''}.`;
            });
            realtimeAlert?.classList.remove('d-none');
        }

        if (pageCount) {
            pageCount.textContent = `${unreadCount} non lue${unreadCount > 1 ? 's' : ''}`;
            pageCount.hidden = !hasUnread;
        }
        if (tabCount) {
            tabCount.textContent = String(unreadCount);
            tabCount.hidden = !hasUnread;
        }
        if (readAll) {
            readAll.hidden = !hasUnread;
        }
    };

    const notificationUrl = (template, uuid) => template
        ? template
            .replace('__notification__', encodeURIComponent(uuid))
            .replace('__uuid__', encodeURIComponent(uuid))
        : '';

    const appendNotification = async (notificationUuid) => {
        if (!notificationList || !notificationUuid) return;

        const statusFilter = realtimeAlert?.dataset.statusFilter || 'all';
        if (statusFilter === 'read') {
            realtimeAlert?.classList.remove('d-none');
            return;
        }

        if (notificationList.querySelector(`[data-notification-id="${CSS.escape(notificationUuid)}"]`)) return;

        try {
            const response = await fetch(notificationUrl(notificationList.dataset.notificationDataUrlTemplate, notificationUuid), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) throw new Error('Notification indisponible.');

            const payload = await response.json();
            const data = payload.data;
            const item = document.createElement('div');
            item.className = 'list-group-item py-3 bg-primary-lt';
            item.dataset.notificationItem = '';
            item.dataset.notificationId = data.id;

            const row = document.createElement('div');
            row.className = 'row align-items-center g-3';
            const iconColumn = document.createElement('div');
            iconColumn.className = 'col-auto';
            const icon = document.createElement('i');
            icon.className = 'ti ti-bell-ringing text-red fs-1';
            iconColumn.append(icon);
            row.append(iconColumn);

            const content = document.createElement('div');
            content.className = 'col';
            const heading = document.createElement('div');
            heading.className = 'd-flex flex-wrap align-items-center gap-2 mb-1';
            const title = document.createElement('h3');
            title.className = 'mb-0 fs-4 fw-semibold text-dark';
            title.textContent = data.title;
            const newBadge = document.createElement('span');
            newBadge.className = 'badge bg-red text-white';
            newBadge.textContent = 'Nouveau';
            const createdAt = document.createElement('time');
            createdAt.className = 'text-secondary small ms-auto';
            createdAt.dateTime = data.created_at || '';
            createdAt.textContent = data.created_at
                ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(data.created_at))
                : '';
            heading.append(title, newBadge, createdAt);

            const message = document.createElement('p');
            message.className = 'text-secondary mb-2';
            message.textContent = data.message;
            content.append(heading, message);

            const actions = document.createElement('div');
            actions.className = 'd-flex flex-wrap gap-2';
            if (data.mission_uuid) {
                actions.append(createNotificationLink(notificationList.dataset.missionUrlTemplate, data.mission_uuid, 'Consulter la mission', 'btn-outline-primary'));
            }
            if (data.missing_form_uuid) {
                actions.append(createNotificationLink(notificationList.dataset.formUrlTemplate, data.missing_form_uuid, 'Voir le dossier manquant', 'btn-outline-secondary'));
            }
            content.append(actions);
            row.append(content);

            const markReadColumn = document.createElement('div');
            markReadColumn.className = 'col-auto';
            const markReadForm = document.createElement('form');
            markReadForm.method = 'post';
            markReadForm.action = notificationUrl(notificationList.dataset.notificationReadUrlTemplate, data.id);
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrf) {
                const token = document.createElement('input');
                token.type = 'hidden';
                token.name = '_token';
                token.value = csrf;
                markReadForm.append(token);
            }
            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'PATCH';
            markReadForm.append(method);
            const markReadButton = document.createElement('button');
            markReadButton.type = 'submit';
            markReadButton.className = 'btn btn-sm btn-outline-success';
            markReadButton.textContent = 'Marquer lu';
            markReadForm.append(markReadButton);
            markReadColumn.append(markReadForm);
            row.append(markReadColumn);
            item.append(row);

            notificationList.querySelector('[data-notification-empty]')?.remove();
            notificationList.prepend(item);
            realtimeAlert?.classList.add('d-none');
        } catch {
            realtimeAlert?.classList.remove('d-none');
        }
    };

    const createNotificationLink = (template, uuid, label, style) => {
        const link = document.createElement('a');
        link.href = template.replace('__uuid__', encodeURIComponent(uuid));
        link.className = `btn btn-sm ${style}`;
        link.textContent = label;

        return link;
    };

    window.Echo
        .private(`user-notifications.${userUuid}`)
        .listen('.user.notification.count.updated', (data) => {
            if (data.user_uuid === userUuid) {
                updateCount(data.unread_count);
                if (data.notification_uuid) {
                    appendNotification(data.notification_uuid);
                }
            }
        });
};

document.addEventListener('DOMContentLoaded', initializeUserNotificationsRealtime);

// Password toggle helper for Tabler input-group-flat
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-password-toggle]');
    if (!btn) return;

    const targetId = btn.getAttribute('data-password-toggle');
    const input = document.getElementById(targetId);
    if (!input) return;

    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';

    const label = isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe';
    btn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
    btn.setAttribute('aria-label', label);
    btn.setAttribute('title', label);

    const showIcon = btn.querySelector('.icon-toggle-show');
    const hideIcon = btn.querySelector('.icon-toggle-hide');

    if (showIcon && hideIcon) {
        showIcon.classList.toggle('d-none', isPassword);
        hideIcon.classList.toggle('d-none', !isPassword);
    }
});

import { risfmHandleRateLimit } from './rate-limiter.js';

export { risfmHandleRateLimit };
window.risfmHandleRateLimit = risfmHandleRateLimit;
