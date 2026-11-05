import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import Button from '@/design-system/Button.vue';
import Input from '@/design-system/Input.vue';
import Modal from '@/design-system/Modal.vue';
import AlertDialog from '@/design-system/AlertDialog.vue';
import Select from '@/design-system/Select.vue';
import Textarea from '@/design-system/Textarea.vue';
import Alert from '@/design-system/Alert.vue';
import Badge from '@/design-system/Badge.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import FormField from '@/design-system/FormField.vue';
import FileInput from '@/design-system/FileInput.vue';
import Pagination from '@/design-system/Pagination.vue';
import Table from '@/design-system/Table.vue';

describe('design system', () => {
    it('Button se deshabilita y marca aria-busy al cargar', () => {
        const w = mount(Button, { props: { loading: true }, slots: { default: 'Guardar' } });
        expect(w.attributes('disabled')).toBeDefined();
        expect(w.attributes('aria-busy')).toBe('true');
        expect(w.find('svg.animate-spin').exists()).toBe(true);
    });

    it('Button muestra loadingText contextual durante la carga', () => {
        const w = mount(Button, {
            props: { loading: true, loadingText: 'Guardando...' },
            slots: { default: 'Guardar y cerrar' },
        });
        expect(w.text()).toContain('Guardando...');
        expect(w.text()).not.toContain('Guardar y cerrar');
        expect(w.find('svg.animate-spin').exists()).toBe(true);
    });

    it('Input expone el error de forma accesible', () => {
        const w = mount(Input, { props: { id: 'email', error: 'Obligatorio' } });
        expect(w.attributes('aria-invalid')).toBe('true');
        expect(w.attributes('aria-describedby')).toBe('email-error');
    });

    it('Select y Textarea asocian el mensaje de validación', () => {
        const select = mount(Select, {
            props: { id: 'course', options: [], error: 'Selecciona un curso' },
        });
        const textarea = mount(Textarea, {
            props: { id: 'description', error: 'Escribe una descripción' },
        });

        expect(select.attributes('aria-describedby')).toBe('course-error');
        expect(textarea.attributes('aria-describedby')).toBe('description-error');
    });

    it('etiqueta el diálogo, gestiona el foco y lo restaura al cerrarse', async () => {
        const opener = document.createElement('button');
        document.body.append(opener);
        opener.focus();
        const wrapper = mount(Modal, {
            attachTo: document.body,
            props: { open: true, title: 'Confirmar acción' },
            slots: { default: '<button>Confirmar</button><button>Cancelar</button>' },
        });
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        const dialog = wrapper.get('[role="dialog"]');
        const [confirm, cancel] = wrapper.findAll('button');

        expect(dialog.attributes('aria-labelledby')).toBe(dialog.get('h2').attributes('id'));
        expect(document.activeElement).toBe(confirm.element);

        cancel.element.focus();
        await cancel.trigger('keydown', { key: 'Tab' });
        expect(document.activeElement).toBe(confirm.element);
        confirm.element.focus();
        await confirm.trigger('keydown', { key: 'Tab', shiftKey: true });
        expect(document.activeElement).toBe(cancel.element);
        await dialog.trigger('keydown', { key: 'Escape' });
        expect(wrapper.emitted('close')).toHaveLength(1);

        await wrapper.setProps({ open: false });
        await wrapper.vm.$nextTick();
        expect(document.activeElement).toBe(opener);

        wrapper.unmount();
        opener.remove();
    });

    it('FormField muestra el mensaje de error', () => {
        const w = mount(FormField, { props: { id: 'x', label: 'Nombre', error: 'Inválido' } });
        expect(w.find('[role=alert]').text()).toBe('Inválido');
    });

    it('Alert muestra contenido accesible para cada variante', () => {
        const variants = [
            ['info', 'status'],
            ['success', 'status'],
            ['warning', 'status'],
            ['danger', 'alert'],
        ] as const;

        for (const [variant, role] of variants) {
            const alert = mount(Alert, {
                props: { variant, title: 'Aviso' },
                slots: { default: 'Detalles del aviso' },
            });

            expect(alert.attributes('role')).toBe(role);
            expect(alert.text()).toContain('Aviso');
            expect(alert.text()).toContain('Detalles del aviso');
            expect(alert.find('svg').exists()).toBe(true);
        }
    });

    it('AlertDialog presenta una confirmación visual con acciones accesibles', async () => {
        const wrapper = mount(AlertDialog, {
            props: {
                open: true,
                variant: 'danger',
                title: '¿Eliminar el curso?',
                message: 'Esta acción no se puede deshacer.',
                confirmLabel: 'Eliminar curso',
            },
        });

        expect(wrapper.get('[role="alertdialog"]').attributes('aria-describedby')).toBe(
            wrapper.get('p').attributes('id'),
        );
        expect(wrapper.text()).toContain('Esta acción no se puede deshacer.');
        expect(wrapper.get('[role="alertdialog"]').get('h2').text()).toBe('¿Eliminar el curso?');
        expect(wrapper.get('[role="alertdialog"]').get('svg')).toBeDefined();
        expect(wrapper.get('[role="alertdialog"]').findAll('button')[0]?.text()).toBe('Cancelar');

        await wrapper.get('button.bg-danger').trigger('click');
        expect(wrapper.emitted('confirm')).toHaveLength(1);

        wrapper.unmount();
    });

    it('Badge y EmptyState renderizan contenido', () => {
        expect(mount(Badge, { slots: { default: 'Activo' } }).text()).toBe('Activo');
        expect(mount(EmptyState, { props: { title: 'Sin datos' } }).text()).toContain('Sin datos');
    });

    it('Pagination deshabilita los extremos y emite cambios', async () => {
        const w = mount(Pagination, { props: { page: 1, lastPage: 3 } });
        const [prev, next] = w.findAll('button');
        expect(prev.attributes('disabled')).toBeDefined();
        expect(next.attributes('aria-label')).toBe('Siguiente');
        await next.trigger('click');
        expect(w.emitted('change')?.[0]).toEqual([2]);
    });

    it('Table renderiza filas y estado de carga', () => {
        const columns = [{ key: 'name', label: 'Nombre' }];
        expect(mount(Table, { props: { columns, rows: [{ name: 'Ana' }] } }).text()).toContain('Ana');
        expect(mount(Table, { props: { columns, rows: [], loading: true } }).text()).toContain('Cargando');
    });

    it('FileInput muestra el archivo elegido, permite quitarlo y conserva el input accesible', async () => {
        const w = mount(FileInput, { props: { id: 'f', buttonLabel: 'Elegir imagen' } });
        expect(w.text()).toContain('Elegir imagen');
        expect(w.text()).toContain('Ningún archivo seleccionado');
        expect(w.find('input#f[type="file"]').exists()).toBe(true);

        await w.setProps({ modelValue: new File(['x'], 'portada.png', { type: 'image/png' }) });
        expect(w.get('[data-testid="file-name"]').text()).toContain('portada.png');
        expect(w.text()).toContain('Cambiar archivo');

        await w.get('button[aria-label="Quitar archivo seleccionado"]').trigger('click');
        expect(w.emitted('update:modelValue')?.at(-1)).toEqual([null]);
    });
});
