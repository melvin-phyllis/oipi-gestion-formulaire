import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const projectRoot = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const blade = readFileSync(join(projectRoot, 'resources/views/backups/index.blade.php'), 'utf8');
const scriptMatch = blade.match(/@push\('scripts'\)\s*<script\b[^>]*>([\s\S]*?)<\/script>/);
assert.ok(scriptMatch, 'Le script réel de la page doit être disponible.');
const pageScript = scriptMatch[1].replace(/\{\{[^}]+\}\}/g, '/backups');
const pageMarkup = blade.slice(0, scriptMatch.index);

test('backup rendering keeps hostile fields, attributes, errors and confirmations inert', () => {
    const directory = mkdtempSync(join(tmpdir(), 'risfm-backup-security-'));
    const filename = join(directory, 'backup-security.html');
    const fixtureScript = `
        window.tabler = {
            Modal: class {
                show() {}
                static getInstance() { return { hide() {} }; }
            }
        };
        window.scrollTo = () => {};
        const payload = '<img class="xss-probe" src="invalid" onerror="window.xssExecuted=true">';
        const attributePayload = '\\" data-xss-probe=\\"true\\"><img class=\\"xss-probe\\" src=\\"invalid\\" onerror=\\"window.xssExecuted=true\\">';
        const uuid = '12345678-1234-4234-8234-123456789012';
        const maliciousBackup = {
            uuid,
            code: payload,
            type: payload,
            file_name: attributePayload,
            creator_name: payload,
            size_bytes: 1024,
            created_at: '2026-10-13T12:00:00Z'
        };
        let malformedMetadata = false;
        let successResponses = false;
        let failList = false;
        window.fetch = async (url, options = {}) => {
            if (options.method) {
                return {
                    ok: successResponses,
                    json: async () => ({
                        message: payload,
                        data: { code: payload },
                        safety_backup: { code: payload, file_name: attributePayload },
                        errors: successResponses ? undefined : { current_password: [payload] }
                    })
                };
            }
            if (failList) throw new Error(payload);
            return {
                ok: true,
                json: async () => ({
                    data: [maliciousBackup],
                    meta: malformedMetadata
                        ? { total: payload, current_page: attributePayload, last_page: attributePayload, per_page: payload }
                        : { total: 1, current_page: 1, last_page: 1, per_page: 20 }
                })
            };
        };

        document.addEventListener('DOMContentLoaded', async () => {
            const checks = [];
            const check = (condition, label) => {
                if (!condition) throw new Error(label);
                checks.push(label);
            };
            const wait = () => new Promise(resolve => setTimeout(resolve, 20));
            const inert = () => document.querySelector('.xss-probe') === null && !window.xssExecuted;
            const restore = async () => {
                document.querySelector('.btn-open-restore').click();
                const phrase = document.getElementById('restore-phrase-input');
                const password = document.getElementById('restore-password-input');
                phrase.value = 'RESTAURER OIPI';
                password.value = 'test-only-password';
                phrase.dispatchEvent(new Event('input'));
                password.dispatchEvent(new Event('input'));
                document.getElementById('btn-submit-restore').click();
                await wait();
            };
            try {
                await window.loadBackups(1);
                await wait();
                check(inert(), 'table payloads remain inert');
                check(document.getElementById('backups-tbody').textContent.includes(payload), 'creator is displayed as literal text');
                const restoreButton = document.querySelector('.btn-open-restore');
                check(restoreButton.dataset.file === attributePayload, 'quoted filename round-trips safely');
                check(restoreButton.dataset.code === payload, 'code attribute round-trips safely');
                check(!restoreButton.hasAttribute('data-xss-probe'), 'payload cannot create an attribute');
                check(document.querySelector('#backups-tbody a').getAttribute('href') === '/backups/' + uuid + '/download', 'download route is preserved');

                document.getElementById('btn-submit-create').click();
                await wait();
                check(document.getElementById('alerts-container').textContent.includes(payload) && inert(), 'creation errors are literal text');

                await restore();
                check(document.getElementById('restore-target-file').textContent === attributePayload, 'restore confirmation preserves filename');
                check(document.getElementById('restore-modal-alert').textContent.includes(payload) && inert(), 'restore errors are literal text');

                document.querySelector('.btn-open-delete').click();
                document.getElementById('btn-submit-delete').click();
                await wait();
                check(document.getElementById('delete-target-file').textContent === attributePayload, 'delete confirmation preserves filename');
                check(document.getElementById('delete-modal-alert').textContent.includes(payload) && inert(), 'delete errors are literal text');

                successResponses = true;
                await restore();
                check(document.getElementById('alerts-container').textContent.includes(attributePayload) && inert(), 'safety backup details are literal text');

                malformedMetadata = true;
                await window.loadBackups(1);
                check(!document.getElementById('pagination-info').querySelector('img') && inert(), 'pagination metadata cannot inject HTML');
                failList = true;
                await window.loadBackups(1);
                check(document.getElementById('backups-tbody').textContent.includes(payload) && inert(), 'list errors are literal text');
                document.getElementById('security-result').textContent = 'PASS:' + checks.length;
            } catch (error) {
                document.getElementById('security-result').textContent = 'FAIL:' + error.message;
            }
        });
    `;

    writeFileSync(filename, `<!doctype html><html><head><meta charset="utf-8"><meta name="csrf-token" content="test-token"></head><body>${pageMarkup}<pre id="security-result">PENDING</pre><script>${pageScript}</script><script>${fixtureScript}</script></body></html>`);

    try {
        const browser = spawnSync(process.env.BROWSER_BIN || 'google-chrome', [
            '--headless', '--no-sandbox', '--disable-gpu', '--disable-extensions',
            '--disable-background-networking', '--no-first-run',
            '--user-data-dir=' + join(directory, 'chrome-profile'),
            '--virtual-time-budget=3000', '--dump-dom', 'file://' + filename,
        ], { encoding: 'utf8', timeout: 30000, maxBuffer: 4 * 1024 * 1024 });

        assert.equal(browser.status, 0, browser.error?.message || browser.stderr);
        const result = browser.stdout.match(/<pre id="security-result">([^<]*)<\/pre>/)?.[1];
        assert.equal(result, 'PASS:14', result || 'Le navigateur n’a pas terminé les contrôles.');
    } finally {
        rmSync(directory, { recursive: true, force: true });
    }
});
