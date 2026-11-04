import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import TeacherPicker from '@/components/courses/TeacherPicker.vue';

const teachers = Array.from({ length: 25 }, (_, index) => ({ id: index + 1, name: `Docente ${index + 1}` }));

function mountPicker(props: { teachers?: typeof teachers; assignedIds?: number[]; modelValue?: number[] } = {}) {
    return mount(TeacherPicker, {
        props: { teachers, assignedIds: [4], modelValue: [4], ...props },
    });
}

describe('TeacherPicker', () => {
    it('muestra una lista con scroll limitado y 10 filas', () => {
        const list = mountPicker().get('[data-testid="teacher-list"]');

        expect(list.classes()).toContain('overflow-y-auto');
        expect(list.findAll('li')).toHaveLength(10);
        expect(list.find('li').text()).toContain('Docente 4');
    });

    it('muestra el mensaje de sin datos', () => {
        const wrapper = mountPicker({ teachers: [], assignedIds: [], modelValue: [] });

        expect(wrapper.text()).toContain('No hay docentes disponibles.');
        expect(wrapper.find('#teacher-search').exists()).toBe(false);
    });

    it('filtra con el buscador, limpia y muestra mensaje sin resultados', async () => {
        const wrapper = mountPicker();
        const search = wrapper.get('#teacher-search');

        await search.setValue('docente 2');
        expect(wrapper.findAll('li').length).toBeGreaterThan(1);

        await search.setValue('zzz');
        expect(wrapper.text()).toContain('No se encontraron docentes');
        expect(wrapper.text()).not.toContain('No hay docentes disponibles.');

        await wrapper.get('button[aria-label="Limpiar búsqueda"]').trigger('click');
        expect(wrapper.findAll('li')).toHaveLength(10);
    });

    it('emite la selección conservando las ocultas por el filtro', async () => {
        const wrapper = mountPicker();

        await wrapper.get('#teacher-search').setValue('Docente 7');
        await wrapper.get('#teacher-7').setValue(true);

        expect(wrapper.emitted('update:modelValue')?.[0][0]).toEqual([4, 7]);
    });

    it('permite alcanzar el buscador por etiqueta', () => {
        expect(mountPicker().get('label[for="teacher-search"]').text()).toContain('Buscar docente');
    });
});
