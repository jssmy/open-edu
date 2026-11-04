import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import SearchableSelect from '@/design-system/SearchableSelect.vue';

const teachers = Array.from({ length: 25 }, (_, index) => ({
    value: String(index + 1),
    label: `Docente ${index + 1}`,
}));

describe('SearchableSelect', () => {
    it('abre el dropdown con scroll para máximo 5 elementos visibles y permite buscar y seleccionar', async () => {
        const wrapper = mount(SearchableSelect, {
            props: {
                id: 'schedule-teacher',
                options: teachers,
                modelValue: '1',
                searchPlaceholder: 'Buscar docente...',
                noResultsMessage: 'No se encontraron docentes.',
            },
        });

        expect(wrapper.get('[data-testid="searchable-select-trigger"]').text()).toContain('Docente 1');

        await wrapper.get('[data-testid="searchable-select-trigger"]').trigger('click');

        const list = wrapper.get('[data-testid="searchable-select-list"]');
        expect(list.classes()).toContain('overflow-y-auto');
        expect(list.classes()).toContain('max-h-[13.75rem]');
        expect(list.attributes('style')).toContain('max-height: 13.75rem');
        expect(list.findAll('li')).toHaveLength(25);

        const searchInput = wrapper.get('[data-testid="searchable-select-input"]');
        await searchInput.setValue('docente 24');
        expect(wrapper.findAll('[role="option"]')).toHaveLength(1);

        await wrapper.get('[role="option"]').trigger('click');
        expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['24']);
        expect(wrapper.find('[data-testid="searchable-select-popover"]').exists()).toBe(false);
    });

    it('distingue entre sin opciones y sin resultados para la búsqueda', async () => {
        const emptyWrapper = mount(SearchableSelect, {
            props: {
                id: 'empty-select',
                options: [],
                emptyMessage: 'No hay docentes disponibles.',
            },
        });

        await emptyWrapper.get('[data-testid="searchable-select-trigger"]').trigger('click');
        expect(emptyWrapper.get('[data-testid="searchable-select-empty"]').text()).toBe(
            'No hay docentes disponibles.',
        );

        const wrapper = mount(SearchableSelect, {
            props: {
                id: 'teacher-select',
                options: teachers,
                noResultsMessage: 'No se encontraron docentes.',
            },
        });

        await wrapper.get('[data-testid="searchable-select-trigger"]').trigger('click');
        await wrapper.get('[data-testid="searchable-select-input"]').setValue('inexistente');
        expect(wrapper.get('[data-testid="searchable-select-no-results"]').text()).toBe(
            'No se encontraron docentes.',
        );
    });
});

