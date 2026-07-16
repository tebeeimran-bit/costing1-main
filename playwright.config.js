import { defineConfig } from '@playwright/test';

export default defineConfig({
    testDir: './tests/e2e', timeout: 30_000, retries: 1, workers: 1,
    use: { baseURL: process.env.E2E_BASE_URL || 'http://127.0.0.1:8000', trace: 'retain-on-failure', screenshot: 'only-on-failure' },
    projects: [{ name: 'edge', use: { channel: 'msedge' } }],
    webServer: process.env.E2E_NO_SERVER ? undefined : { command: 'php artisan serve --host=127.0.0.1 --port=8000', url: 'http://127.0.0.1:8000/login', reuseExistingServer: true, timeout: 30_000 },
});
