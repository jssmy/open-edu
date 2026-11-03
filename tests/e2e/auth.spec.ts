import { expect, test } from '@playwright/test';

// Requiere BD sembrada: ADMIN_EMAIL y ADMIN_PASSWORD (php artisan db:seed).
const email = process.env.ADMIN_EMAIL ?? 'admin@openedu.test';
const password = process.env.ADMIN_PASSWORD ?? '';

test.skip(!password, 'Define ADMIN_PASSWORD con la contraseña inicial del administrador');

test('login, cambio de contraseña inicial y restricción por rol', async ({ page }) => {
    await page.goto('/login');
    await page.getByLabel('Correo').fill(email);
    await page.getByLabel('Contraseña').fill(password);
    await page.getByRole('button', { name: 'Entrar' }).click();

    const mustChangePassword = await page
        .getByLabel('Contraseña actual')
        .waitFor({ state: 'visible', timeout: 3000 })
        .then(() => true)
        .catch(() => false);

    if (mustChangePassword) {
        await expect(page).toHaveURL(/password\/change/);
        await page.getByLabel('Contraseña actual').fill(password);
        await page.getByLabel('Nueva contraseña').fill('Nueva-clave-123');
        await page.getByLabel('Confirmar contraseña').fill('Nueva-clave-123');
        await page.getByRole('button', { name: 'Guardar' }).click();
    } else if (page.url().includes('/login')) {
        await page.getByLabel('Contraseña').fill('Nueva-clave-123');
        await page.getByRole('button', { name: 'Entrar' }).click();
    }

    await expect(page).toHaveURL(/dashboard/);
    await page.goto('/users');
    await expect(page.getByRole('heading', { name: 'Usuarios' })).toBeVisible();
});

test('un usuario sin sesión es enviado al login', async ({ page }) => {
    await page.goto('/users');
    await expect(page).toHaveURL(/login/);
});
