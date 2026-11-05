import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import MyClasses from '@/pages/Student/MyClasses.vue';
import type { StudentClassItem } from '@/pages/Student/MyClasses.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    Link: {
        props: ['href'],
        template: '<a :href="href"><slot /></a>',
    },
    router: { get: vi.fn() },
    usePage: () => ({ props: { errors: {} } }),
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

describe('Student/MyClasses.vue', () => {
    const mockClasses: StudentClassItem[] = [
        {
            id: 1,
            enrollment_id: 1,
            offering_id: 10,
            course_id: 100,
            name: 'Clase en Curso',
            excerpt: 'Descripción de clase activa',
            cover_url: '/courses/100/cover',
            units_count: 4,
            group_code: 'GRP-01',
            modality: 'Virtual',
            starts_on: '2026-11-01',
            ends_on: '2026-12-01',
            teachers: [{ id: 1, name: 'Profesor Uno' }],
            status: 'in_progress',
            can_access: true,
            is_finished: false,
            progress: {
                completed_lessons: 2,
                total_lessons: 4,
                percentage: 50,
            },
        },
        {
            id: 2,
            enrollment_id: 2,
            offering_id: 20,
            course_id: 200,
            name: 'Clase Próxima a Empezar',
            excerpt: 'Descripción de clase futura',
            cover_url: '/courses/200/cover',
            units_count: 2,
            group_code: 'GRP-02',
            modality: 'Presencial',
            starts_on: '2026-12-15',
            ends_on: '2027-01-15',
            teachers: [{ id: 2, name: 'Profesor Dos' }],
            status: 'scheduled',
            can_access: false,
            is_finished: false,
            progress: {
                completed_lessons: 0,
                total_lessons: 6,
                percentage: 0,
            },
        },
        {
            id: 3,
            enrollment_id: 3,
            offering_id: 30,
            course_id: 300,
            name: 'Clase Finalizada',
            excerpt: 'Descripción de clase culminada',
            cover_url: '/courses/300/cover',
            units_count: 5,
            group_code: 'GRP-03',
            modality: 'Híbrida',
            starts_on: '2026-09-01',
            ends_on: '2026-10-01',
            teachers: [{ id: 3, name: 'Profesor Tres' }],
            status: 'finished',
            can_access: true,
            is_finished: true,
            progress: {
                completed_lessons: 5,
                total_lessons: 5,
                percentage: 100,
            },
        },
    ];

    function createWrapper(
        classes = mockClasses,
        filters = { status: 'in_progress' },
    ) {
        return mount(MyClasses, {
            props: {
                classes,
                filters,
            },
        });
    }

    it('renderiza por defecto clases en curso sin bloques de avisos superiores', () => {
        const wrapper = createWrapper();

        // No debe mostrar avisos superiores
        expect(wrapper.find('#heading-upcoming-classes').exists()).toBe(false);
        expect(wrapper.find('#heading-started-classes').exists()).toBe(false);

        // Catálogo principal filtrado por defecto en "in_progress"
        expect(wrapper.text()).toContain('Clase en Curso');
        expect(wrapper.text()).toContain('Entrar a Clase');
        expect(wrapper.text()).not.toContain('Clase Finalizada');
    });

    it('inhabilita el botón de acción con fecha de inicio cuando can_access es falso', async () => {
        const wrapper = createWrapper();

        const select = wrapper.find('#filter-status');
        await select.setValue('scheduled');

        expect(wrapper.text()).toContain('Clase Próxima a Empezar');
        expect(wrapper.text()).toContain('Inicia el 15/12/2026');
        const disabledButtons = wrapper.findAll('button[disabled]');
        expect(disabledButtons.some((b) => b.text().includes('Inicia el 15/12/2026'))).toBe(true);
    });

    it('muestra el botón en modo consulta cuando la clase está finalizada', async () => {
        const wrapper = createWrapper();

        const select = wrapper.find('#filter-status');
        await select.setValue('finished');

        expect(wrapper.text()).toContain('Clase Finalizada');
        expect(wrapper.text()).toContain('Ver contenido (Consulta)');
    });
});
