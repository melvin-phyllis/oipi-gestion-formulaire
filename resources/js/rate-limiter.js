/**
 * Gestionnaire générique de réponse HTTP 429 (Trop de requêtes) pour les actions JavaScript sensibles.
 *
 * Affiche le message reçu, extrait retry_after (secondes) pour décompte en temps réel,
 * désactive temporairement le ou les boutons concernés, puis les réactive à la fin du délai.
 * Ne relance JAMAIS automatiquement la requête.
 *
 * @param {Object} options
 * @param {Response} [options.response] - L'objet Response fetch
 * @param {Object} [options.data] - Les données JSON parsées { message, retry_after }
 * @param {HTMLElement|Object} [options.button] - Le bouton principal concerné
 * @param {HTMLElement[]|Object[]} [options.buttons] - Liste optionnelle de boutons à désactiver
 * @param {Function} [options.onAlert] - Callback d'affichage du message: (message, retryAfter) => void
 * @param {Function} [options.onTick] - Callback exécuté à chaque seconde: (remainingSeconds) => void
 * @param {Function} [options.onFinish] - Callback exécuté à l'expiration du compte à rebours
 * @param {Function} [options.renderButton] - Callback de rendu personnalisé: (button, remainingSeconds) => void
 * @param {Function} [options.restoreButton] - Callback de restauration personnalisée: (button) => void
 * @returns {number} Nombre de secondes d'attente
 */
export const risfmHandleRateLimit = ({
    response = null,
    data = null,
    button = null,
    buttons = null,
    onAlert = null,
    onTick = null,
    onFinish = null,
    renderButton = null,
    restoreButton = null
} = {}) => {
    let headerRetryAfter = null;
    if (response && response.headers) {
        if (typeof response.headers.get === 'function') {
            headerRetryAfter = response.headers.get('Retry-After') || response.headers.get('retry-after');
        } else if (typeof response.headers === 'object') {
            headerRetryAfter = response.headers['Retry-After'] || response.headers['retry-after'];
        }
    }

    let retryAfter = Number(data?.retry_after ?? data?.retryAfter ?? headerRetryAfter);
    if (!Number.isFinite(retryAfter) || retryAfter <= 0) {
        retryAfter = 60;
    }
    retryAfter = Math.ceil(retryAfter);

    const message = data?.message || `Trop de requêtes ont été effectuées. Veuillez patienter ${retryAfter} seconde(s) avant de renouveler l'action.`;

    const targetButtons = [];
    if (button) targetButtons.push(button);
    if (Array.isArray(buttons)) targetButtons.push(...buttons);
    const validButtons = targetButtons.filter((btn) => btn && typeof btn === 'object' && 'disabled' in btn);

    validButtons.forEach((btn) => {
        if (btn._risfmRateLimitTimer) {
            clearInterval(btn._risfmRateLimitTimer);
            btn._risfmRateLimitTimer = null;
        }

        btn._isRateLimited = true;
        btn.disabled = true;
        if (btn.classList && typeof btn.classList.remove === 'function') {
            btn.classList.remove('btn-loading');
        }

        if (typeof renderButton === 'function') {
            renderButton(btn, retryAfter);
        } else {
            if (typeof btn._risfmOriginalHtml !== 'string') {
                btn._risfmOriginalHtml = btn.innerHTML;
            }
            btn.innerHTML = `<i class="ti ti-hourglass-empty me-1" aria-hidden="true"></i> Patienter (${retryAfter}s)`;
        }
    });

    if (typeof onAlert === 'function') {
        onAlert(message, retryAfter);
    }

    let remaining = retryAfter;
    const timer = setInterval(() => {
        remaining--;

        if (remaining <= 0) {
            clearInterval(timer);

            validButtons.forEach((btn) => {
                btn._risfmRateLimitTimer = null;
                btn._isRateLimited = false;
                btn.disabled = false;
                if (typeof restoreButton === 'function') {
                    restoreButton(btn);
                } else if (typeof btn._risfmOriginalHtml === 'string') {
                    btn.innerHTML = btn._risfmOriginalHtml;
                    delete btn._risfmOriginalHtml;
                }
            });

            if (typeof onFinish === 'function') {
                onFinish();
            }
        } else {
            validButtons.forEach((btn) => {
                if (typeof renderButton === 'function') {
                    renderButton(btn, remaining);
                } else {
                    btn.innerHTML = `<i class="ti ti-hourglass-empty me-1" aria-hidden="true"></i> Patienter (${remaining}s)`;
                }
            });

            if (typeof onTick === 'function') {
                onTick(remaining);
            }
        }
    }, 1000);

    validButtons.forEach((btn) => {
        btn._risfmRateLimitTimer = timer;
    });

    return retryAfter;
};
