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
    await editor.press('ControlOrMeta+A');
    await page
        .getByRole('toolbar', { name: `Formato de ${label}` })
        .getByRole('button', { name: 'Negrita' })
        .focus();
    await page.keyboard.press('Enter');
    await editor.press('ControlOrMeta+End');
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
    await page.getByRole('textbox', { name: 'Objetivos' }).fill('Validar el flujo de cursos');
    await page.getByLabel('Video de presentación (YouTube o Vimeo)').fill('https://youtu.be/abcdefghijk');

    const descriptionEditor = page.getByRole('textbox', { name: 'Descripción' });
    const paragraphBox = await descriptionEditor.locator('p').first().boundingBox();
    await descriptionEditor
        .locator('p')
        .first()
        .click({ position: { x: (paragraphBox?.width ?? 10) - 2, y: 5 } });
    await page.getByRole('button', { name: 'Mostrar opciones de imagen' }).first().click();
    await page.getByLabel('O URL HTTPS de una imagen externa').fill('https://images.example.test/course.png');
    await page.getByLabel('Texto alternativo').fill('Diagrama del curso');
    await page.getByRole('button', { name: 'Insertar imagen por URL' }).click();
    await page.getByRole('button', { name: 'Mostrar opciones de imagen' }).first().click();

    await page.getByRole('button', { name: 'Crear curso' }).click();

    await expect(page).toHaveURL(/\/courses\/\d+$/);
    await expect(page.getByRole('heading', { name: courseName })).toBeVisible();
    await page.getByRole('link', { name: 'Editar curso' }).click();
    await expect(page).toHaveURL(/\/courses\/\d+\/edit$/);
    await expect(page.getByRole('heading', { name: 'Editar curso' })).toBeVisible();
    await page.getByRole('link', { name: 'Volver al curso' }).click();
    await expect(page).toHaveURL(/\/courses\/\d+$/);
    await page.setViewportSize({ width: 375, height: 812 });
    await expect(page.getByRole('textbox').first().locator('strong')).toHaveText(descriptionText);
    await expect(page.locator('img[alt="Diagrama del curso"]')).toHaveAttribute(
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
    await expect(page.getByRole('textbox').first().locator('strong')).toHaveText(descriptionText);
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
    await page.getByRole('link', { name: 'Volver al curso' }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Cancelar' }).click();
    await expect(page).toHaveURL(/\/courses\/\d+\/edit$/);
    await expect(page.getByLabel('Nombre')).toHaveValue('Nombre sin guardar');

    await page.getByRole('link', { name: 'Volver al curso' }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Salir sin guardar' }).click();
    await expect(page).toHaveURL(/\/courses\/\d+$/);

    await page.getByLabel('Nueva unidad').fill('Unidad cambios');
    await page.getByRole('button', { name: 'Agregar unidad' }).click();
    await page.getByRole('link', { name: 'Agregar lección' }).click();
    await expect(page).toHaveURL(/\/units\/\d+\/lessons\/create$/);
    await page.getByLabel('Título').fill('Lección sin guardar');
    await page.getByRole('link', { name: 'Volver al curso' }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Cancelar' }).click();
    await expect(page).toHaveURL(/\/units\/\d+\/lessons\/create$/);
    await expect(page.getByLabel('Título')).toHaveValue('Lección sin guardar');

    await page.getByRole('link', { name: 'Volver al curso' }).click();
    await page.getByRole('alertdialog').getByRole('button', { name: 'Salir sin guardar' }).click();
    await expect(page).toHaveURL(/\/courses\/\d+$/);
});

const tinyPng = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
    'base64',
);

test('lists courses as cards, filters them and manages the cover image', async ({ page }) => {
    await signInAsAdmin(page);

    const courseName = `Curso portada ${Date.now()}`;
    await page.goto('/courses/create');
    await page.getByLabel('Nombre').fill(courseName);
    await fillAndBoldEditor(page, 'Descripción', 'Descripción con portada');
    await page.getByRole('textbox', { name: 'Objetivos' }).fill('Objetivo de la portada');
    await page.locator('#course-cover').setInputFiles({
        name: 'portada.png',
        mimeType: 'image/png',
        buffer: tinyPng,
    });
    await expect(page.getByText('portada.png')).toBeVisible();
    await page.getByRole('button', { name: 'Crear curso' }).click();
    await expect(page).toHaveURL(/\/courses\/\d+$/);

    await page.getByRole('link', { name: 'Editar curso' }).click();
    await expect(page.getByAltText('Vista previa de la portada')).toBeVisible();
    await expect(page.getByRole('textbox', { name: 'Descripción' }).locator('strong')).toHaveText(
        'Descripción con portada',
    );
    await expect(page.getByRole('textbox', { name: 'Objetivos' })).toHaveText('Objetivo de la portada');

    await page.getByRole('button', { name: 'Quitar imagen' }).click();
    await page.getByRole('button', { name: 'Guardar cambios' }).click();
    await expect(page).toHaveURL(/\/courses\/\d+$/);
    await page.getByRole('link', { name: 'Editar curso' }).click();
    await expect(page.getByText('Sin imagen: se usará una portada generada por el sistema.')).toBeVisible();

    await page.goto('/courses');
    await page.getByLabel('Buscar').first().fill(courseName);
    await expect(page).toHaveURL(/q=/);
    const card = page.locator('li', { has: page.getByRole('heading', { name: courseName }) });
    await expect(card).toHaveCount(1);
    const cover = card.locator('img');
    await expect(cover).toHaveAttribute('src', /\/courses\/\d+\/cover/);
    const response = await page.request.get((await cover.getAttribute('src')) ?? '');
    expect(response.headers()['content-type']).toContain('image/svg+xml');

    await page.getByLabel('Buscar').first().fill(`sin-coincidencias-${Date.now()}`);
    await expect(page.getByText('Sin resultados')).toBeVisible();
});
