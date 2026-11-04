import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import CourseCard from '@/components/courses/CourseCard.vue';

describe('CourseCard', () => {
    const baseCourse = {
        id: 7,
        name: 'Python desde Cero',
        excerpt: 'Curso práctico de programación en Python.',
        cover_url: '/courses/7/cover?v=1',
        units_count: 4,
        status: 'published' as const,
        teachers: [{ id: 1, name: 'Prof. Lucía García Rojas' }],
    };

    it('renders course cover, title, excerpt, teachers, units and default status badge', () => {
        const wrapper = mount(CourseCard, {
            props: { course: baseCourse },
            global: {
                stubs: {
                    Link: {
                        props: ['href'],
                        template: '<a :href="href"><slot /></a>',
                    },
                },
            },
        });

        expect(wrapper.find('img').attributes('src')).toBe('/courses/7/cover?v=1');
        expect(wrapper.find('h2').text()).toBe('Python desde Cero');
        expect(wrapper.text()).toContain('Curso práctico de programación en Python.');
        expect(wrapper.text()).toContain('Prof. Lucía García Rojas · 4 unidades');
        expect(wrapper.text()).toContain('Publicado');
    });

    it('allows overriding badges and footer slots for the enrollments catalog', () => {
        const wrapper = mount(CourseCard, {
            props: { course: baseCourse },
            slots: {
                badges: '<span data-testid="custom-badge">15 vacantes disponibles</span>',
                footer: '<button data-testid="custom-action">Ver programación y cupos</button>',
            },
        });

        expect(wrapper.find('[data-testid="custom-badge"]').text()).toBe('15 vacantes disponibles');
        expect(wrapper.find('[data-testid="custom-action"]').text()).toBe('Ver programación y cupos');
    });
});

