import { test, expect } from '@playwright/test';

test('login page and protected navigation remain healthy', async ({ page }) => {
    await page.goto('/login');
    await expect(page).toHaveTitle(/Costing|Login/i);
    await expect(page.locator('form')).toBeVisible();
    if (!process.env.E2E_USER || !process.env.E2E_PASSWORD) return;
    await page.locator('input[name="email"]').fill(process.env.E2E_USER);
    await page.locator('input[name="password"]').fill(process.env.E2E_PASSWORD);
    await page.locator('button[type="submit"]').click();
    await expect(page).not.toHaveURL(/login/);
    await page.goto('/project');
    await expect(page.locator('body')).toContainText(/Project|Costing/i);
    await page.goto('/help-center');
    await expect(page.getByText('Apa yang ingin Anda pelajari?')).toBeVisible();
});
