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
