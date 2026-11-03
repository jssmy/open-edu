import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import Button from '@/design-system/Button.vue';
import Input from '@/design-system/Input.vue';
import Alert from '@/design-system/Alert.vue';
import Badge from '@/design-system/Badge.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import FormField from '@/design-system/FormField.vue';
import Pagination from '@/design-system/Pagination.vue';
import Table from '@/design-system/Table.vue';

describe('design system', () => {
    it('Button se deshabilita y marca aria-busy al cargar', () => {
        const w = mount(Button, { props: { loading: true }, slots: { default: 'Guardar' } });
        expect(w.attributes('disabled')).toBeDefined();
        expect(w.attributes('aria-busy')).toBe('true');
    });

    it('Input expone el error de forma accesible', () => {
        const w = mount(Input, { props: { id: 'email', error: 'Obligatorio' } });
        expect(w.attributes('aria-invalid')).toBe('true');
        expect(w.attributes('aria-describedby')).toBe('email-error');
    });

    it('FormField muestra el mensaje de error', () => {
        const w = mount(FormField, { props: { id: 'x', label: 'Nombre', error: 'Inválido' } });
        expect(w.find('[role=alert]').text()).toBe('Inválido');
    });

    it('Alert danger usa role alert', () => {
        expect(mount(Alert, { props: { variant: 'danger' } }).attributes('role')).toBe('alert');
    });

    it('Badge y EmptyState renderizan contenido', () => {
        expect(mount(Badge, { slots: { default: 'Activo' } }).text()).toBe('Activo');
        expect(mount(EmptyState, { props: { title: 'Sin datos' } }).text()).toContain('Sin datos');
    });

    it('Pagination deshabilita los extremos y emite cambios', async () => {
        const w = mount(Pagination, { props: { page: 1, lastPage: 3 } });
        const [prev, next] = w.findAll('button');
        expect(prev.attributes('disabled')).toBeDefined();
        await next.trigger('click');
        expect(w.emitted('change')?.[0]).toEqual([2]);
    });

    it('Table renderiza filas y estado de carga', () => {
        const columns = [{ key: 'name', label: 'Nombre' }];
        expect(mount(Table, { props: { columns, rows: [{ name: 'Ana' }] } }).text()).toContain('Ana');
        expect(mount(Table, { props: { columns, rows: [], loading: true } }).text()).toContain('Cargando');
    });
});
