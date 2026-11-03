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
    }

    await expect(page).toHaveURL(/dashboard/);
}

test('registers a student and enrolls them in a published course', async ({ page }) => {
    await signInAsAdmin(page);

    const suffix = Date.now();
    const studentName = `Estudiante E2E ${suffix}`;
    const studentEmail = `estudiante-${suffix}@example.test`;
    const courseName = `Curso de inscripción E2E ${suffix}`;

    await page.goto('/students');
    await page.getByRole('button', { name: 'Registrar estudiante' }).click();
    await page.getByLabel('Nombre').fill(studentName);
    await page.getByLabel('Correo').fill(studentEmail);
    await page.getByLabel('Contraseña temporal').fill('temporal-123');
    await page.getByRole('button', { name: 'Registrar', exact: true }).click();
    await expect(page.getByRole('link', { name: studentName })).toBeVisible();

    await page.goto('/courses');
    await page.getByRole('button', { name: 'Nuevo curso' }).click();
    await page.getByLabel('Nombre').fill(courseName);
    await page.getByLabel('Descripción').fill('Curso para probar inscripciones');
    await page.getByLabel('Objetivos').fill('Inscribir estudiantes');
    await page.getByRole('button', { name: 'Crear curso' }).click();
    await page.getByRole('link', { name: courseName }).click();

    await page.getByLabel('Estado').selectOption('published');
    await page.getByRole('button', { name: 'Actualizar estado' }).click();
    await page.getByRole('link', { name: 'Gestionar inscripciones' }).click();
    await page.getByRole('button', { name: 'Inscribir estudiante' }).click();
    await page
        .getByLabel('Estudiante', { exact: true })
        .selectOption({ label: `${studentName} · ${studentEmail}` });
    await page.getByRole('button', { name: 'Inscribir', exact: true }).click();

    await expect(page.getByRole('link', { name: studentName })).toBeVisible();
    await page.getByRole('link', { name: studentName }).click();
    await expect(page.getByRole('link', { name: courseName })).toBeVisible();
});
