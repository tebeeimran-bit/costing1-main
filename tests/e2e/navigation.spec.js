import { test, expect } from '@playwright/test';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const fixture = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../public/templates/templatepartlist.xlsx');

async function login(page) {
    await page.goto('/login');
    await page.locator('input[name="login"]').fill(process.env.E2E_USER || 'e2e-admin@example.test');
    await page.locator('input[name="password"]').fill(process.env.E2E_PASSWORD || 'E2E-password-2026');
    await page.locator('button[type="submit"]').click();
    await expect(page).not.toHaveURL(/login/);
}

async function dismissTour(page) {
    await page.waitForTimeout(800);
    const skip = page.locator('#costing-guided-tour [data-tour-action="skip"]');
    if (await skip.isVisible().catch(() => false)) await skip.click();
}

test('login page and protected navigation remain healthy', async ({ page }) => {
    await page.goto('/login');
    await expect(page).toHaveTitle(/Costing|Login/i);
    await expect(page.locator('form')).toBeVisible();
    await login(page);
    await page.goto('/project');
    await expect(page.locator('body')).toContainText(/Project|Costing/i);
    await page.goto('/help-center');
    await expect(page.getByText('Apa yang ingin Anda pelajari?')).toBeVisible();
});

test('user can create a project with engineering documents through the browser', async ({ page }) => {
    await login(page);
    await page.goto('/tracking-documents/new');
    await dismissTour(page);
    await page.locator('select[name="business_category_id"]').selectOption({ index: 1 });
    await page.locator('select[name="customer_id"]').selectOption({ index: 1 });
    await page.locator('input[name="model"]').fill('E2E-CREATED-MODEL');
    await page.locator('input[name="assy_no"]').fill('E2E-CREATED-001');
    await page.locator('input[name="assy_name"]').fill('E2E Browser Created Harness');
    await page.locator('select[name="pic_engineering"]').selectOption({ index: 1 });
    await page.locator('select[name="pic_marketing"]').selectOption({ index: 1 });
    await page.locator('input[name="partlist_file"]').setInputFiles(fixture);
    await page.locator('input[name="umh_file"]').setInputFiles(fixture);
    await page.getByRole('button', { name: 'Simpan Project' }).click();
    await expect(page).toHaveURL(/project/);
    await expect(page.locator('body')).toContainText('E2E-CREATED-001');
});

test('costing can pass submit, digital approval, and marketing workflow in browser', async ({ page }) => {
    await login(page);
    await page.goto('/project?search=E2E-APPROVAL-001');
    await dismissTour(page);

    if (await page.getByRole('button', { name: 'Submit Approval' }).count() === 0 && await page.getByText('Submitted to Marketing').count() > 0) return;
    await page.getByRole('button', { name: 'Lihat Semua Part' }).click();
    page.once('dialog', dialog => dialog.accept());
    await page.getByRole('button', { name: 'Submit Approval' }).click();

    await page.getByRole('button', { name: 'Lihat Semua Part' }).click();
    page.once('dialog', dialog => dialog.accept('APPROVE'));
    await page.getByRole('button', { name: 'Approve COGM' }).click();

    await page.getByRole('button', { name: 'Lihat Semua Part' }).click();
    page.once('dialog', dialog => dialog.accept());
    await page.getByRole('button', { name: 'Send Marketing' }).click();
    await expect(page.locator('body')).toContainText('Submitted to Marketing');
});

test('login remains accessible on a mobile viewport', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/login');
    await expect(page.locator('form')).toBeVisible();
    await expect(page.locator('input[name="login"]')).toHaveAttribute('required', '');
    await page.keyboard.press('Tab');
    await expect(page.locator(':focus')).toBeVisible();
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1);
    expect(overflow).toBeFalsy();
});

test('project and costing pages stay inside the browser performance budget', async ({ page }) => {
    await login(page);
    const response = await page.goto('/project?search=E2E-APPROVAL-001');
    await dismissTour(page);
    expect(response?.status()).toBe(200);
    expect(response?.headers()['server-timing']).toMatch(/app;dur=/);
    const projectDuration = await page.evaluate(() => performance.getEntriesByType('navigation')[0].duration);
    expect(projectDuration).toBeLessThan(8000);
    await page.getByRole('button', { name: 'Lihat Semua Part' }).click();
    const formUrl = await page.getByRole('link', { name: 'Form Costing' }).getAttribute('href');
    const started = Date.now();
    await page.goto(formUrl);
    await expect(page.locator('#costingForm')).toBeVisible();
    expect(Date.now() - started).toBeLessThan(10000);
});
