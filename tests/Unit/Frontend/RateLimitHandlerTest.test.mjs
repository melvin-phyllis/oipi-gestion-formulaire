import assert from 'node:assert/strict';
import test from 'node:test';
import { risfmHandleRateLimit } from '../../../resources/js/rate-limiter.js';

test('risfmHandleRateLimit extracts retry_after and message, disables button, and counts down without auto-retry', async () => {
    let originalHtml = '<i class="ti ti-check"></i> Valider';
    const mockButton = {
        disabled: false,
        innerHTML: originalHtml,
        classList: {
            remove(cls) {
                this[cls] = false;
            }
        }
    };

    let receivedAlertMessage = null;
    let receivedAlertSecs = null;
    let ticks = [];
    let finished = false;

    const data = {
        message: 'Trop de tentatives consécutives. Patientez.',
        retry_after: 2
    };

    const response = {
        status: 429,
        headers: new Map([['Retry-After', '2']])
    };

    const resultSeconds = risfmHandleRateLimit({
        response,
        data,
        button: mockButton,
        onAlert: (msg, secs) => {
            receivedAlertMessage = msg;
            receivedAlertSecs = secs;
        },
        onTick: (rem) => {
            ticks.push(rem);
        },
        onFinish: () => {
            finished = true;
        }
    });

    assert.equal(resultSeconds, 2);
    assert.equal(receivedAlertMessage, 'Trop de tentatives consécutives. Patientez.');
    assert.equal(receivedAlertSecs, 2);
    assert.equal(mockButton.disabled, true);
    assert.ok(mockButton.innerHTML.includes('Patienter (2s)'));

    // Attendre 2.2 secondes pour que le compte à rebours se termine
    await new Promise((resolve) => setTimeout(resolve, 2200));

    assert.equal(finished, true);
    assert.equal(mockButton.disabled, false);
    assert.equal(mockButton.innerHTML, originalHtml);
    assert.ok(ticks.includes(1));
});

test('risfmHandleRateLimit handles multiple buttons and header fallback', async () => {
    const btn1 = { disabled: false, innerHTML: 'Action 1', classList: { remove() {} } };
    const btn2 = { disabled: false, innerHTML: 'Action 2', classList: { remove() {} } };

    const response = {
        status: 429,
        headers: {
            get(name) {
                if (name.toLowerCase() === 'retry-after') return '1';
                return null;
            }
        }
    };

    let finishCalled = false;

    risfmHandleRateLimit({
        response,
        buttons: [btn1, btn2],
        onFinish: () => {
            finishCalled = true;
        }
    });

    assert.equal(btn1.disabled, true);
    assert.equal(btn2.disabled, true);
    assert.ok(btn1.innerHTML.includes('Patienter (1s)'));
    assert.ok(btn2.innerHTML.includes('Patienter (1s)'));

    await new Promise((resolve) => setTimeout(resolve, 1200));

    assert.equal(finishCalled, true);
    assert.equal(btn1.disabled, false);
    assert.equal(btn2.disabled, false);
    assert.equal(btn1.innerHTML, 'Action 1');
    assert.equal(btn2.innerHTML, 'Action 2');
});

test('risfmHandleRateLimit supports renderButton and restoreButton without modifying innerHTML', async () => {
    const iconObj = { className: 'ti ti-sparkles me-1', hidden: false };
    const spinnerObj = { hidden: true };
    const textObj = { textContent: 'Générer l’identifiant' };

    const mockButton = {
        disabled: false,
        innerHTML: '<i id="icon"></i><span id="spinner"></span><span id="text"></span>',
        classList: { remove() {} }
    };

    const initialInnerHTML = mockButton.innerHTML;

    let restored = false;

    risfmHandleRateLimit({
        data: { retry_after: 1, message: 'Attendez' },
        button: mockButton,
        renderButton: (btn, remaining) => {
            spinnerObj.hidden = true;
            iconObj.className = 'ti ti-hourglass-empty me-1';
            iconObj.hidden = false;
            textObj.textContent = `Patienter (${remaining}s)`;
        },
        restoreButton: (btn) => {
            restored = true;
            spinnerObj.hidden = true;
            iconObj.className = 'ti ti-sparkles me-1';
            iconObj.hidden = false;
            textObj.textContent = 'Générer l’identifiant';
        }
    });

    assert.equal(mockButton.disabled, true);
    assert.equal(mockButton._isRateLimited, true);
    assert.equal(mockButton.innerHTML, initialInnerHTML, 'innerHTML should NOT be overwritten');
    assert.equal(textObj.textContent, 'Patienter (1s)');
    assert.equal(iconObj.className, 'ti ti-hourglass-empty me-1');

    await new Promise((resolve) => setTimeout(resolve, 1200));

    assert.equal(restored, true);
    assert.equal(mockButton.disabled, false);
    assert.equal(mockButton._isRateLimited, false);
    assert.equal(mockButton.innerHTML, initialInnerHTML, 'innerHTML should remain unchanged after finish');
    assert.equal(textObj.textContent, 'Générer l’identifiant');
    assert.equal(iconObj.className, 'ti ti-sparkles me-1');

    // Simulate subsequent click loading state using the exact same referenced elements
    spinnerObj.hidden = false;
    iconObj.hidden = true;
    textObj.textContent = 'Génération en cours...';

    assert.equal(spinnerObj.hidden, false);
    assert.equal(iconObj.hidden, true);
    assert.equal(textObj.textContent, 'Génération en cours...');
});

test('restore validation function respects _isRateLimited during active countdown', async () => {
    const btnSubmitRestore = {
        disabled: false,
        innerHTML: 'Confirmer la restauration',
        classList: { remove() {} }
    };

    const state = {
        phrase: 'RESTAURER OIPI',
        password: 'SecretPassword123'
    };

    function validateRestoreInputs() {
        const isPhraseValid = (state.phrase.trim() === 'RESTAURER OIPI');
        if (btnSubmitRestore._isRateLimited) {
            btnSubmitRestore.disabled = true;
            return;
        }
        btnSubmitRestore.disabled = !(isPhraseValid && state.password.length > 0);
    }

    // Initial valid state -> button is enabled
    validateRestoreInputs();
    assert.equal(btnSubmitRestore.disabled, false);

    // Rate limit triggers
    risfmHandleRateLimit({
        data: { retry_after: 1, message: 'Trop de tentatives' },
        button: btnSubmitRestore,
        onFinish: () => {
            validateRestoreInputs();
        }
    });

    assert.equal(btnSubmitRestore._isRateLimited, true);
    assert.equal(btnSubmitRestore.disabled, true);

    // User types into inputs during countdown
    state.phrase = 'RESTAURER OIPI ';
    state.password = 'NewPassword';
    validateRestoreInputs();

    // MUST remain disabled!
    assert.equal(btnSubmitRestore.disabled, true, 'Button must remain disabled during countdown even when inputs change');

    await new Promise((resolve) => setTimeout(resolve, 1200));

    // Countdown finished: _isRateLimited is false, and onFinish calls validateRestoreInputs
    assert.equal(btnSubmitRestore._isRateLimited, false);
    assert.equal(btnSubmitRestore.disabled, false, 'Button should be re-enabled after countdown finishes because inputs are valid');

    // If inputs become invalid after countdown:
    state.phrase = 'INVALID';
    validateRestoreInputs();
    assert.equal(btnSubmitRestore.disabled, true);
});
