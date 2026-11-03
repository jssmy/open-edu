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

async function fillAndBoldEditor(page: Page, label: string, text: string) {
    const editor = page.getByRole('textbox', { name: label });
    await editor.fill(text);
    await editor.press('Control+A');
    await editor.locator('xpath=../..').getByRole('button', { name: 'Negrita' }).focus();
    await page.keyboard.press('Enter');
    await editor.press('Control+End');
    await expect(editor.locator('strong')).toHaveText(text);
}

test('creates and publishes a course with a unit and lesson', async ({ page }) => {
    await signInAsAdmin(page);

    const courseName = `Curso E2E ${Date.now()}`;
    const descriptionText = 'Curso creado durante la prueba E2E';
    await page.goto('/courses');
    await page.getByRole('link', { name: 'Nuevo curso' }).click();
    await expect(page).toHaveURL('/courses/create');
    await page.getByLabel('Nombre').fill(courseName);
    await fillAndBoldEditor(page, 'Descripción', descriptionText);
    await page.getByLabel('Objetivos').fill('Validar el flujo de cursos');
    await page.getByLabel('Video de presentación (YouTube o Vimeo)').fill('https://youtu.be/abcdefghijk');

    await page.getByRole('button', { name: 'Mostrar opciones de imagen' }).first().click();
    await page.getByLabel('O URL HTTPS de una imagen externa').fill('https://images.example.test/course.png');
    await page.getByLabel('Texto alternativo').fill('Diagrama del curso');
    await page.getByRole('button', { name: 'Insertar imagen por URL' }).click();

    await page.getByRole('button', { name: 'Crear curso' }).click();

    await expect(page).toHaveURL(/\/courses\/\d+$/);
    await expect(page.getByRole('heading', { name: courseName })).toBeVisible();
    await page.getByRole('link', { name: 'Editar curso' }).click();
    await expect(page).toHaveURL(/\/courses\/\d+\/edit$/);
    await expect(page.getByRole('heading', { name: 'Editar curso' })).toBeVisible();
    await page.getByRole('link', { name: 'Volver al curso' }).click();
    await expect(page).toHaveURL(/\/courses\/\d+$/);
    await page.setViewportSize({ width: 375, height: 812 });
    await expect(page.getByRole('textbox', { name: 'Descripción' }).locator('strong')).toHaveText(
        descriptionText,
    );
    await expect(page.locator('[contenteditable="true"] img[alt="Diagrama del curso"]')).toHaveAttribute(
        'src',
        'https://images.example.test/course.png',
    );
    await expect(page.getByTitle(`Video de presentación: ${courseName}`)).toHaveAttribute(
        'src',
        'https://www.youtube-nocookie.com/embed/abcdefghijk',
    );
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);

    await page.getByLabel('Estado').selectOption('published');
    await page.getByRole('button', { name: 'Actualizar estado' }).click();

    await page.getByLabel('Nueva unidad').fill('Unidad E2E');
    await page.getByRole('button', { name: 'Agregar unidad' }).click();
    await page.getByRole('link', { name: 'Agregar lección' }).click();
    await expect(page).toHaveURL(/\/units\/\d+\/lessons\/create$/);
    await expect(page.getByRole('heading', { name: 'Nueva lección' })).toBeVisible();
    await page.getByLabel('Título').fill('Lección E2E');
    await fillAndBoldEditor(page, 'Contenido de la lección', 'Contenido de prueba');
    await page.getByRole('button', { name: 'Crear lección' }).click();

    await expect(page).toHaveURL(/\/courses\/\d+$/);
    await expect(page.getByLabel('Estado')).toHaveValue('published');
    await expect(page.getByText('Lección E2E')).toBeVisible();
    await page.reload();
    await expect(page.getByRole('textbox', { name: 'Descripción' }).locator('strong')).toHaveText(
        descriptionText,
    );
    await expect(page.getByText('Lección E2E')).toBeVisible();
});

test('asks before discarding course or lesson edits', async ({ page }) => {
    await signInAsAdmin(page);

    await page.goto('/courses/create');
    await page.getByLabel('Nombre').fill(`Curso cambios ${Date.now()}`);
    await page.getByRole('button', { name: 'Crear curso' }).click();
    await expect(page).toHaveURL(/\/courses\/\d+$/);

    await page.getByRole('link', { name: 'Editar curso' }).click();
    await page.getByLabel('Nombre').fill('Nombre sin guardar');
    page.once('dialog', (dialog) => dialog.dismiss());
    await page.getByRole('link', { name: 'Volver al curso' }).click();
    await expect(page).toHaveURL(/\/courses\/\d+\/edit$/);
    await expect(page.getByLabel('Nombre')).toHaveValue('Nombre sin guardar');

    page.once('dialog', (dialog) => dialog.accept());
    await page.getByRole('link', { name: 'Volver al curso' }).click();
    await expect(page).toHaveURL(/\/courses\/\d+$/);

    await page.getByLabel('Nueva unidad').fill('Unidad cambios');
    await page.getByRole('button', { name: 'Agregar unidad' }).click();
    await page.getByRole('link', { name: 'Agregar lección' }).click();
    await expect(page).toHaveURL(/\/units\/\d+\/lessons\/create$/);
    await page.getByLabel('Título').fill('Lección sin guardar');
    page.once('dialog', (dialog) => dialog.dismiss());
    await page.getByRole('link', { name: 'Volver al curso' }).click();
    await expect(page).toHaveURL(/\/units\/\d+\/lessons\/create$/);
    await expect(page.getByLabel('Título')).toHaveValue('Lección sin guardar');

    page.once('dialog', (dialog) => dialog.accept());
    await page.getByRole('link', { name: 'Volver al curso' }).click();
    await expect(page).toHaveURL(/\/courses\/\d+$/);
});
