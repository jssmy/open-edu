import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import SchedulingIndex from '@/pages/Scheduling/Index.vue';

// Mock AppLayout, ScheduleCalendar, Head, Link to simplify rendering
vi.mock('@/layouts/AppLayout.vue', () => ({
    default: {
        name: 'AppLayout',
        template: '<div><slot /></div>',
    },
}));

vi.mock('@/design-system/ScheduleCalendar.vue', () => ({
    default: {
        name: 'ScheduleCalendar',
        props: ['blocks', 'editable'],
        template: '<div data-testid="schedule-calendar"></div>',
    },
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: { template: '<a><slot /></a>' },
    router: { get: vi.fn(), delete: vi.fn(), post: vi.fn(), put: vi.fn() },
    useForm: () => ({
        processing: false,
        clearErrors: vi.fn(),
        reset: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
    }),
}));

describe('Scheduling Index Page', () => {
    const mockProps = {
        schedules: {
            data: [],
            meta: { page: 1, last_page: 1, total: 0, from: 0, to: 0 },
        },
        calendarBlocks: [],
        filters: {
            q: '',
            course: '',
            teacher: '10',
            classroom: '',
            status: '',
        },
        courses: [{ id: 1, name: 'Matemática I' }],
        teachers: [
            { id: 10, name: 'Profesor Jirafales' },
            { id: 20, name: 'Doña Florinda' },
        ],
        classrooms: [{ id: 1, name: 'Aula 101', capacity: 30 }],
        pendingCount: 0,
    };

    it('renders the top teacher selector and table filters separately', () => {
        const wrapper = mount(SchedulingIndex, {
            props: mockProps,
            global: {
                stubs: {
                    Modal: true,
                },
            },
        });

        // The top teacher selection section should exist
        expect(wrapper.text()).toContain('Gestión por docente');
        expect(wrapper.find('#schedule-teacher-select').exists()).toBe(true);

        // The table section header and table search form should exist
        expect(wrapper.text()).toContain('Listado de ofertas y grupos programados');
        expect(wrapper.find('#filter-q').exists()).toBe(true);
        expect(wrapper.find('#filter-course').exists()).toBe(true);
        expect(wrapper.find('#filter-classroom').exists()).toBe(true);
        expect(wrapper.find('#filter-status').exists()).toBe(true);
    });
});

