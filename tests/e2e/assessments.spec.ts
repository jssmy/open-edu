import { expect, test, type Page } from '@playwright/test';

const adminEmail = process.env.ADMIN_EMAIL ?? 'admin@openedu.test';
const adminPassword = process.env.ADMIN_PASSWORD ?? '';
const temporaryPassword = 'Temporal-Evaluacion-123';
const studentPassword = 'Estudiante-Evaluacion-123';

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
        await page.getByLabel('Nueva contraseña').fill(studentPassword);
        await page.getByLabel('Confirmar contraseña').fill(studentPassword);
        await page.getByRole('button', { name: 'Guardar' }).click();
    } else if (page.url().includes('/login')) {
        await page.getByLabel('Contraseña').fill(studentPassword);
        await page.getByRole('button', { name: 'Entrar' }).click();
    }

    await expect(page).toHaveURL(/dashboard/);
}

async function fillAndBoldEditor(page: Page, label: string, text: string) {
    const editor = page.getByRole('textbox', { name: label });
    await editor.fill(text);
    await editor.press('Control+A');
    await editor.press('Control+B');
    await expect(editor.locator('strong')).toHaveText(text);
}

test('creates an assessment and lets an enrolled student submit it', async ({ page }) => {
    await signInAsAdmin(page);

    const suffix = Date.now();
    const studentName = `Estudiante evaluación ${suffix}`;
    const studentEmail = `evaluacion-${suffix}@example.test`;
    const courseName = `Curso evaluación ${suffix}`;

    await page.goto('/students');
    await page.getByRole('button', { name: 'Registrar estudiante' }).click();
    await page.getByLabel('Nombre').fill(studentName);
    await page.getByLabel('Correo').fill(studentEmail);
    await page.getByLabel('Contraseña temporal').fill(temporaryPassword);
    await page.getByRole('button', { name: 'Registrar', exact: true }).click();

    await page.goto('/courses');
    await page.getByRole('link', { name: 'Nuevo curso' }).click();
    await page.getByLabel('Nombre').fill(courseName);
    await fillAndBoldEditor(page, 'Descripción', 'Curso para probar evaluaciones');
    await page.getByLabel('Objetivos').fill('Resolver una evaluación');
    await page.getByRole('button', { name: 'Crear curso' }).click();
    await expect(page.getByText('Curso creado.')).toBeVisible();
    await expect(page).toHaveURL(/\/courses\/\d+$/);
    const courseUrl = page.url();

    await page.getByLabel('Estado').selectOption('published');
    await page.getByRole('button', { name: 'Actualizar estado' }).click();
    await page.getByRole('link', { name: 'Nueva evaluación' }).click();
    await page.getByLabel('Título').fill('Evaluación E2E');
    await page.getByLabel('Intentos permitidos').fill('1');
    await page.getByLabel('Puntaje mínimo').fill('2');
    await fillAndBoldEditor(page, 'Instrucciones de la evaluación', 'Lee cada pregunta con atención.');
    await fillAndBoldEditor(page, 'Enunciado de la pregunta 1', '¿Qué estudia la biología?');
    await page.getByLabel('Puntaje', { exact: true }).fill('2');
    await page.getByLabel('Alternativa 1', { exact: true }).fill('Los seres vivos');
    await page.getByLabel('Alternativa 2', { exact: true }).fill('Los minerales');
    await page.getByRole('button', { name: 'Crear evaluación' }).click();
    await expect(page.getByRole('heading', { name: 'Editar evaluación' })).toBeVisible();
    await expect(page).toHaveURL(/\/assessments\/\d+\/edit$/);
    const assessmentUrl = page.url();
    await page.reload();
    await expect(
        page.getByRole('textbox', { name: 'Instrucciones de la evaluación' }).locator('strong'),
    ).toHaveText('Lee cada pregunta con atención.');
    await expect(
        page.getByRole('textbox', { name: 'Enunciado de la pregunta 1' }).locator('strong'),
    ).toHaveText('¿Qué estudia la biología?');

    await page.getByLabel('Título').fill('Cambio sin guardar');
    page.once('dialog', (dialog) => dialog.dismiss());
    await page.getByRole('link', { name: 'Volver al curso' }).click();
    await expect(page).toHaveURL(assessmentUrl);
    await expect(page.getByLabel('Título')).toHaveValue('Cambio sin guardar');

    page.once('dialog', (dialog) => dialog.accept());
    await page.getByRole('link', { name: 'Volver al curso' }).click();
    await expect(page).toHaveURL(courseUrl);
    await page.goto(assessmentUrl);
    await expect(page.getByLabel('Título')).toHaveValue('Evaluación E2E');

    await page.getByRole('button', { name: 'Publicar evaluación' }).click();
    await expect(page.getByRole('status').filter({ hasText: 'Evaluación publicada.' }).first()).toBeVisible();

    await page.goto(courseUrl);
    await page.getByRole('link', { name: 'Gestionar inscripciones' }).click();
    await page.getByRole('button', { name: 'Inscribir estudiante' }).click();
    await page
        .getByLabel('Estudiante', { exact: true })
        .selectOption({ label: `${studentName} · ${studentEmail}` });
    await page.getByRole('button', { name: 'Inscribir', exact: true }).click();
    await page.getByRole('button', { name: 'Cerrar sesión' }).click();

    await page.goto('/login');
    await page.getByLabel('Correo').fill(studentEmail);
    await page.getByLabel('Contraseña').fill(temporaryPassword);
    await page.getByRole('button', { name: 'Entrar' }).click();
    await page.getByLabel('Contraseña actual').fill(temporaryPassword);
    await page.getByLabel('Nueva contraseña').fill(studentPassword);
    await page.getByLabel('Confirmar contraseña').fill(studentPassword);
    await page.getByRole('button', { name: 'Guardar' }).click();

    await page.goto('/my/courses');
    await page.getByRole('link', { name: courseName }).click();
    await page.getByRole('link', { name: 'Rendir evaluación' }).click();
    await expect(page.locator('[aria-label="Instrucciones de la evaluación"] strong')).toHaveText(
        'Lee cada pregunta con atención.',
    );
    await expect(page.locator('[aria-label="Enunciado de la pregunta 1"] strong')).toHaveText(
        '¿Qué estudia la biología?',
    );
    await page.getByLabel('Los seres vivos').check();
    await page.getByRole('button', { name: 'Enviar evaluación' }).click();

    await expect(page.getByText('Aprobado', { exact: false })).toBeVisible();
    await expect(page.getByText('2 de 2 puntos', { exact: false })).toBeVisible();
    await expect(page.locator('[aria-label="Enunciado de la pregunta 1"] strong')).toHaveText(
        '¿Qué estudia la biología?',
    );
});
