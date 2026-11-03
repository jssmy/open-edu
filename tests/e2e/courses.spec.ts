import { expect, test, type Page } from '@playwright/test';

const email = process.env.ADMIN_EMAIL ?? 'admin@openedu.test';
const initialPassword = process.env.ADMIN_PASSWORD ?? '';
const changedPassword = 'Nueva-clave-123';

test.skip(!initialPassword, 'Define ADMIN_PASSWORD con la contraseña inicial del administrador');

async function signInAsAdmin(page: Page) {
    await page.goto('/login');
    await page.getByLabel('Correo').fill(email);
    await page.getByLabel('Contraseña').fill(initialPassword);
    await page.getByRole('button', { name: 'Entrar' }).click();

    const mustChangePassword = await page
        .getByLabel('Contraseña actual')
        .waitFor({ state: 'visible', timeout: 3000 })
        .then(() => true)
        .catch(() => false);

    if (mustChangePassword) {
        await page.getByLabel('Contraseña actual').fill(initialPassword);
        await page.getByLabel('Nueva contraseña').fill(changedPassword);
        await page.getByLabel('Confirmar contraseña').fill(changedPassword);
        await page.getByRole('button', { name: 'Guardar' }).click();
    } else if (page.url().includes('/login')) {
        await page.getByLabel('Contraseña').fill(changedPassword);
        await page.getByRole('button', { name: 'Entrar' }).click();
    } else if (page.url().includes('/login')) {
        await page.getByLabel('Contraseña').fill(changedPassword);
        await page.getByRole('button', { name: 'Entrar' }).click();
    }

    await expect(page).toHaveURL(/dashboard/);
}

test('creates and publishes a course with a unit and lesson', async ({ page }) => {
    await signInAsAdmin(page);

    const courseName = `Curso E2E ${Date.now()}`;
    await page.goto('/courses');
    await page.getByRole('button', { name: 'Nuevo curso' }).click();
    await page.getByLabel('Nombre').fill(courseName);
    await page.getByLabel('Descripción').fill('Curso creado durante la prueba E2E');
    await page.getByLabel('Objetivos').fill('Validar el flujo de cursos');
    await page.getByRole('button', { name: 'Crear curso' }).click();

    await page.getByRole('link', { name: courseName }).click();
    await expect(page.getByRole('heading', { name: courseName })).toBeVisible();
    await page.getByLabel('Estado').selectOption('published');
    await page.getByRole('button', { name: 'Actualizar estado' }).click();

    await page.getByLabel('Nueva unidad').fill('Unidad E2E');
    await page.getByRole('button', { name: 'Agregar unidad' }).click();
    await page.getByRole('button', { name: 'Agregar lección' }).click();
    await page.getByLabel('Título').fill('Lección E2E');
    await page.getByLabel('Contenido o URL').fill('Contenido de prueba');
    await page.getByRole('button', { name: 'Guardar lección' }).click();

    await expect(page.getByLabel('Estado')).toHaveValue('published');
    await expect(page.getByText('Lección E2E')).toBeVisible();
});
