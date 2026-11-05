import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import MyCourses from '@/pages/Study/MyCourses.vue';
import type { MyCourseItem } from '@/pages/Study/MyCourses.vue';

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

describe('MyCourses', () => {
    const dummyCourses: MyCourseItem[] = [
        {
            id: 1,
            name: 'Curso en Progreso',
            excerpt: 'Descripción breve 1',
            cover_url: '/courses/1/cover',
            units_count: 3,
            status: 'published',
            teachers: [{ id: 1, name: 'Profesor Uno' }],
            progress: {
                completed_lessons: 1,
                total_lessons: 2,
                percentage: 50,
                status: 'in_progress',
            },
        },
        {
            id: 2,
            name: 'Curso Completado',
            excerpt: 'Descripción breve 2',
            cover_url: '/courses/2/cover',
            units_count: 5,
            status: 'published',
            teachers: [{ id: 2, name: 'Profesor Dos' }],
            progress: {
                completed_lessons: 4,
                total_lessons: 4,
                percentage: 100,
                status: 'completed',
            },
        },
        {
            id: 3,
            name: 'Curso No Iniciado',
            excerpt: 'Descripción breve 3',
            cover_url: '/courses/3/cover',
            units_count: 2,
            status: 'published',
            teachers: [{ id: 3, name: 'Profesor Tres' }],
            progress: {
                completed_lessons: 0,
                total_lessons: 5,
                percentage: 0,
                status: 'not_started',
            },
        },
    ];

    function createWrapper(courses = dummyCourses, filters = { status: 'in_progress' }) {
        return mount(MyCourses, {
            props: {
                courses,
                filters,
            },
        });
    }

    it('filters by "in_progress" by default, showing progress percentage and CourseCard', () => {
        const wrapper = createWrapper();

        // Must display in-progress course
        expect(wrapper.text()).toContain('Curso en Progreso');
        expect(wrapper.text()).toContain('50%');
        expect(wrapper.text()).toContain('1 / 2 (50%)');
        expect(wrapper.text()).toContain('50% de avance');
        expect(wrapper.text()).toContain('En proceso');

        // Must not display completed or not started courses by default
        expect(wrapper.text()).not.toContain('Curso Completado');
        expect(wrapper.text()).not.toContain('Curso No Iniciado');
    });

    it('can filter courses by completed', async () => {
        const wrapper = createWrapper();

        const select = wrapper.find('#filter-status');
        await select.setValue('completed');

        expect(wrapper.text()).toContain('Curso Completado');
        expect(wrapper.text()).toContain('100% de avance');
        expect(wrapper.text()).toContain('Completado');
        expect(wrapper.text()).not.toContain('Curso en Progreso');
        expect(wrapper.text()).not.toContain('Curso No Iniciado');
    });

    it('can filter courses by not_started', async () => {
        const wrapper = createWrapper();

        const select = wrapper.find('#filter-status');
        await select.setValue('not_started');

        expect(wrapper.text()).toContain('Curso No Iniciado');
        expect(wrapper.text()).toContain('0% de avance');
        expect(wrapper.text()).toContain('No iniciado');
        expect(wrapper.text()).toContain('Iniciar curso');
        expect(wrapper.text()).not.toContain('Curso en Progreso');
        expect(wrapper.text()).not.toContain('Curso Completado');
    });

    it('can show all courses when selecting all', async () => {
        const wrapper = createWrapper();

        const select = wrapper.find('#filter-status');
        await select.setValue('all');

        expect(wrapper.text()).toContain('Curso en Progreso');
        expect(wrapper.text()).toContain('Curso Completado');
        expect(wrapper.text()).toContain('Curso No Iniciado');
    });

    it('renders empty state when there are no courses matching the filter', async () => {
        const wrapper = createWrapper([dummyCourses[1]]); // Only completed course

        // By default filters by in_progress
        expect(wrapper.text()).toContain('No hay cursos en este estado');
    });
});
