import { expect, test, type Page } from '@playwright/test';

const adminEmail = process.env.ADMIN_EMAIL ?? 'admin@openedu.test';
const adminPassword = process.env.ADMIN_PASSWORD ?? '';
const changedPassword = 'Nueva-Reportes-E2E-123';

test.skip(!adminPassword, 'Define ADMIN_PASSWORD con la contraseña inicial del administrador');

async function signInAsAdmin(page: Page) {
    await page.goto('/login');
    await page.getByLabel('Correo').fill(adminEmail);
    await page.getByLabel('Contraseña').fill(adminPassword);
    await page.getByRole('button', { name: 'Entrar' }).click();

    const changePasswordVisible = await page
        .getByLabel('Contraseña actual')
        .waitFor({ state: 'visible', timeout: 3000 })
        .then(() => true)
        .catch(() => false);

    if (changePasswordVisible) {
        await page.getByLabel('Contraseña actual').fill(adminPassword);
        await page.getByLabel('Nueva contraseña').fill(changedPassword);
        await page.getByLabel('Confirmar contraseña').fill(changedPassword);
        await page.getByRole('button', { name: 'Guardar' }).click();
    } else if (page.url().includes('/login')) {
        await page.getByLabel('Contraseña').fill(changedPassword);
        await page.getByRole('button', { name: 'Entrar' }).click();
    }

    await expect(page).toHaveURL(/dashboard/);
}

test('generates the enrollment report and exports it as CSV', async ({ page }) => {
    await signInAsAdmin(page);

    await page.goto('/reports/enrollments');
    await expect(page.getByRole('heading', { name: 'Reporte de inscripciones' })).toBeVisible();

    const downloadPromise = page.waitForEvent('download');
    await page.getByRole('link', { name: 'Exportar CSV' }).click();
    const download = await downloadPromise;

    expect(download.suggestedFilename()).toBe('enrollments.csv');
});
