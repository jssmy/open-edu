import { expect, test, type Page } from '@playwright/test';

const adminEmail = process.env.ADMIN_EMAIL ?? 'admin@openedu.test';
const adminPassword = process.env.ADMIN_PASSWORD ?? '';
const studentPassword = 'Temporal-E2E-123';
const changedPassword = 'Nueva-E2E-123';

test.skip(!adminPassword, 'Define ADMIN_PASSWORD con la contraseña inicial del administrador');

async function signInAsAdmin(page: Page) {
    await page.goto('/login');
    await page.getByLabel('Correo').fill(adminEmail);
    await page.getByLabel('Contraseña').fill(adminPassword);
    await page.getByRole('button', { name: 'Entrar' }).click();

    if (page.url().includes('/password/change')) {
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

test('lets an enrolled student complete a lesson and see course progress', async ({ page }) => {
    await signInAsAdmin(page);

    const suffix = Date.now();
    const studentName = `Estudiante de estudio ${suffix}`;
    const studentEmail = `estudio-${suffix}@example.test`;
    const courseName = `Curso de estudio ${suffix}`;

    await page.goto('/students');
    await page.getByRole('button', { name: 'Registrar estudiante' }).click();
    await page.getByLabel('Nombre').fill(studentName);
    await page.getByLabel('Correo').fill(studentEmail);
    await page.getByLabel('Contraseña temporal').fill(studentPassword);
    await page.getByRole('button', { name: 'Registrar', exact: true }).click();

    await page.goto('/courses');
    await page.getByRole('button', { name: 'Nuevo curso' }).click();
    await page.getByLabel('Nombre').fill(courseName);
    await page.getByLabel('Descripción').fill('Curso para probar el progreso');
    await page.getByLabel('Objetivos').fill('Completar una lección');
    await page.getByRole('button', { name: 'Crear curso' }).click();
    await page.getByRole('link', { name: courseName }).click();
    await page.getByLabel('Estado').selectOption('published');
    await page.getByRole('button', { name: 'Actualizar estado' }).click();
    await page.getByLabel('Nueva unidad').fill('Unidad de estudio');
    await page.getByRole('button', { name: 'Agregar unidad' }).click();
    await page.getByRole('button', { name: 'Agregar lección' }).click();
    await page.getByLabel('Título').fill('Lección de estudio');
    await page.getByLabel('Contenido o URL').fill('Contenido de lección E2E');
    await page.getByRole('button', { name: 'Guardar lección' }).click();

    await page.getByRole('link', { name: 'Gestionar inscripciones' }).click();
    await page.getByRole('button', { name: 'Inscribir estudiante' }).click();
    await page.getByLabel('Estudiante', { exact: true }).selectOption({
        label: `${studentName} · ${studentEmail}`,
    });
    await page.getByRole('button', { name: 'Inscribir', exact: true }).click();
    await page.getByRole('button', { name: 'Cerrar sesión' }).click();

    await page.goto('/login');
    await page.getByLabel('Correo').fill(studentEmail);
    await page.getByLabel('Contraseña').fill(studentPassword);
    await page.getByRole('button', { name: 'Entrar' }).click();
    await expect(page).toHaveURL(/password\/change/);
    await page.getByLabel('Contraseña actual').fill(studentPassword);
    await page.getByLabel('Nueva contraseña').fill(changedPassword);
    await page.getByLabel('Confirmar contraseña').fill(changedPassword);
    await page.getByRole('button', { name: 'Guardar' }).click();
    await expect(page).toHaveURL(/dashboard/);

    await page.goto('/my/courses');
    await page.getByRole('link', { name: courseName }).click();
    await expect(page.getByText('Contenido de lección E2E')).toBeVisible();
    await page.getByRole('button', { name: 'Marcar como completada' }).click();

    await expect(page.getByRole('progressbar', { name: 'Progreso del curso' })).toHaveAttribute(
        'aria-valuenow',
        '100',
    );
    await expect(page.getByText('Lección completada', { exact: true })).toBeVisible();
});
