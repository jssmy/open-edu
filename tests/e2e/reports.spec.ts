import { expect, test, type Page } from '@playwright/test';
import { readFile } from 'node:fs/promises';

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

    const suffix = Date.now();
    const studentName = `Estudiante reporte E2E ${suffix}`;
    const studentEmail = `reporte-${suffix}@example.test`;
    const courseName = `Curso reporte E2E ${suffix}`;

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
    await page.getByLabel('Descripción').fill('Curso para probar reportes');
    await page.getByLabel('Objetivos').fill('Generar y exportar reportes');
    await page.getByRole('button', { name: 'Crear curso' }).click();
    await expect(page.getByText('Curso creado.')).toBeVisible();
    await page.getByRole('link', { name: courseName }).click();
    await expect(page).toHaveURL(/\/courses\/\d+$/);
    const courseId = new URL(page.url()).pathname.split('/').at(-1);
    await page.getByLabel('Estado').selectOption('published');
    await page.getByRole('button', { name: 'Actualizar estado' }).click();
    await page.getByRole('link', { name: 'Gestionar inscripciones' }).click();
    await page.getByRole('button', { name: 'Inscribir estudiante' }).click();
    await page
        .getByLabel('Estudiante', { exact: true })
        .selectOption({ label: `${studentName} · ${studentEmail}` });
    await page.getByRole('button', { name: 'Inscribir', exact: true }).click();

    await page.goto(`/reports/enrollments?course_id=${courseId}&status=active`);
    await expect(page.getByRole('heading', { name: 'Reporte de inscripciones' })).toBeVisible();
    const enrollmentRow = page.getByRole('row').filter({ hasText: studentName });
    await expect(enrollmentRow).toContainText(courseName);
    await expect(enrollmentRow).toContainText('active');

    const downloadPromise = page.waitForEvent('download');
    await page.getByRole('link', { name: 'Exportar CSV' }).click();
    const download = await downloadPromise;

    expect(download.suggestedFilename()).toBe('enrollments.csv');
    const csvPath = await download.path();
    expect(csvPath).not.toBeNull();
    if (csvPath === null) {
        throw new Error('Playwright did not provide the downloaded CSV path.');
    }

    const csv = await readFile(csvPath, 'utf8');
    expect(csv).toContain(courseName);
    expect(csv).toContain(studentName);
});
