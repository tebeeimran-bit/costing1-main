import { defineConfig } from '@playwright/test';

const port = process.env.E2E_PORT || '8000';
const baseURL = process.env.E2E_BASE_URL || `http://127.0.0.1:${port}`;

export default defineConfig({
    testDir: './tests/e2e', timeout: 30_000, retries: 1, workers: 1,
    use: { baseURL, trace: 'retain-on-failure', screenshot: 'only-on-failure' },
    projects: process.env.CI ? [{ name: 'chromium', use: { browserName: 'chromium' } }] : [{ name: 'edge', use: { channel: 'msedge' } }],
    webServer: process.env.E2E_NO_SERVER ? undefined : { command: `php artisan serve --host=127.0.0.1 --port=${port}`, url: `${baseURL}/login`, reuseExistingServer: true, timeout: 30_000 },
});
