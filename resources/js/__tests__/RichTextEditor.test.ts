import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import RichTextEditor from '@/components/editor/RichTextEditor.vue';
import { emptyRichTextDocument, type RichTextDocument } from '@/types/richText';

async function mountEditor() {
    const wrapper = mount(RichTextEditor, {
        props: {
            id: 'course-description',
            label: 'Descripción',
            modelValue: emptyRichTextDocument(),
            courseId: 42,
        },
        attachTo: document.body,
    });
    await flushPromises();

    return wrapper;
}

describe('RichTextEditor', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
        document.cookie = 'XSRF-TOKEN=; Max-Age=0';
    });

    it('exposes an accessible keyboard-operable toolbar and editor', async () => {
        const wrapper = await mountEditor();
        const textbox = wrapper.get('[contenteditable="true"]');
        const boldButton = wrapper.get('button[aria-label="Negrita"]');

        expect(textbox.attributes('aria-label')).toBe('Descripción');
        expect(textbox.attributes('aria-multiline')).toBe('true');
        expect(wrapper.get('[role="toolbar"]').attributes('aria-label')).toBe('Formato de Descripción');
        expect(boldButton.attributes('aria-pressed')).toBe('false');
        expect(textbox.classes()).toContain('focus-visible:ring-2');

        (boldButton.element as HTMLButtonElement).focus();
        expect(document.activeElement).toBe(boldButton.element);

        wrapper.unmount();
    });

    it('rejects unsafe link and external image protocols', async () => {
        const wrapper = await mountEditor();

        await wrapper.get('button[aria-label="Mostrar opciones de enlace"]').trigger('click');
        await wrapper.get('#course-description-rich-link-url').setValue('javascript:alert(1)');
        await wrapper.get('button[aria-label="Aplicar enlace"]').trigger('click');
        expect(wrapper.get('[role="alert"]').text()).toContain('HTTP o HTTPS');

        await wrapper.get('button[aria-label="Mostrar opciones de imagen"]').trigger('click');
        await wrapper
            .get('#course-description-rich-image-url')
            .setValue('http://images.example.test/photo.png');
        await wrapper.get('#course-description-rich-image-alt').setValue('Diagrama');
        await wrapper.get('button[aria-label="Insertar imagen por URL"]').trigger('click');
        expect(wrapper.findAll('[role="alert"]').some((alert) => alert.text().includes('HTTPS'))).toBe(true);

        wrapper.unmount();
    });

    it('requires descriptive alt text for informative external images', async () => {
        const wrapper = await mountEditor();

        await wrapper.get('button[aria-label="Mostrar opciones de imagen"]').trigger('click');
        await wrapper
            .get('#course-description-rich-image-url')
            .setValue('https://images.example.test/photo.png');
        await wrapper.get('button[aria-label="Insertar imagen por URL"]').trigger('click');
        expect(wrapper.get('[role="alert"]').text()).toContain('texto alternativo');

        await wrapper.get('#course-description-rich-image-alt').setValue('Diagrama del curso');
        await wrapper.get('button[aria-label="Insertar imagen por URL"]').trigger('click');
        const emitted = wrapper.emitted('update:modelValue') as [RichTextDocument][];
        const image = emitted.at(-1)?.[0].content.find((node) => node.type === 'image');

        expect(image?.attrs?.alt).toBe('Diagrama del curso');

        wrapper.unmount();
    });

    it('inserts a decorative HTTPS image with an explicitly empty alternative', async () => {
        const wrapper = await mountEditor();

        await wrapper.get('button[aria-label="Mostrar opciones de imagen"]').trigger('click');
        await wrapper
            .get('#course-description-rich-image-url')
            .setValue('https://images.example.test/photo.png');
        await wrapper.get('input[type="checkbox"]').setValue(true);
        await wrapper.get('button[aria-label="Insertar imagen por URL"]').trigger('click');

        const emitted = wrapper.emitted('update:modelValue') as [RichTextDocument][];
        const updatedDocument = emitted.at(-1)?.[0];
        const image = updatedDocument?.content.find((node) => node.type === 'image');

        expect(image?.attrs?.alt).toBe('');
        expect(image?.attrs?.src).toBe('https://images.example.test/photo.png');

        wrapper.unmount();
    });

    it('uploads an image with the CSRF token and inserts the authorized course URL', async () => {
        const fetchMock = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({
                id: '123e4567-e89b-12d3-a456-426614174000',
                src: '/courses/42/content-images/123e4567-e89b-12d3-a456-426614174000',
            }),
        });
        vi.stubGlobal('fetch', fetchMock);
        document.cookie = 'XSRF-TOKEN=csrf%20token';
        const wrapper = await mountEditor();

        await wrapper.get('button[aria-label="Mostrar opciones de imagen"]').trigger('click');
        await wrapper.get('#course-description-rich-image-alt').setValue('Mapa del curso');
        const fileInput = wrapper.get<HTMLInputElement>('#course-description-rich-image-file');
        Object.defineProperty(fileInput.element, 'files', {
            value: [new globalThis.File(['image bytes'], 'map.png', { type: 'image/png' })],
        });
        await fileInput.trigger('change');
        await wrapper.get('button[aria-label="Subir e insertar imagen"]').trigger('click');

        expect(fetchMock).toHaveBeenCalledWith(
            '/courses/42/content-images',
            expect.objectContaining({
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': 'csrf token',
                },
            }),
        );
        const emitted = wrapper.emitted('update:modelValue') as [RichTextDocument][];
        const image = emitted.at(-1)?.[0].content.find((node) => node.type === 'image');
        expect(image?.attrs).toMatchObject({
            alt: 'Mapa del curso',
            src: '/courses/42/content-images/123e4567-e89b-12d3-a456-426614174000',
        });

        wrapper.unmount();
    });
});
