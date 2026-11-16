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
        category_name: 'Digital',
        category_role_name: 'estudiante',
        teachers: [{ id: 1, name: 'Prof. Lucía García Rojas' }],
    };

    const linkStub = {
        props: ['href'],
        template: '<a :href="href"><slot /></a>',
    };

    it('renders category badge (without role), links cover preview and title to detail, and omits eye icon button', () => {
        const wrapper = mount(CourseCard, {
            props: { course: baseCourse },
            global: {
                stubs: {
                    Link: linkStub,
                },
            },
        });

        expect(wrapper.find('img').attributes('src')).toBe('/courses/7/cover?v=1');
        expect(wrapper.find('h2').text()).toBe('Python desde Cero');
        expect(wrapper.text()).toContain('Digital');
        expect(wrapper.text()).not.toContain('Rol:');
        expect(wrapper.text()).toContain('Curso práctico de programación en Python.');
        expect(wrapper.text()).toContain('Prof. Lucía García Rojas · 4 unidades');
        expect(wrapper.text()).toContain('Publicado');

        // Cover preview and title link to detail
        expect(wrapper.find('[data-testid="course-card-cover-link-7"]').attributes('href')).toBe('/courses/7');
        expect(wrapper.find('[data-testid="course-card-title-link-7"]').attributes('href')).toBe('/courses/7');

        // No default footer or eye icon button when no footer slot is provided
        expect(wrapper.find('button').exists()).toBe(false);
    });

    it('allows overriding badges and footer slots for the enrollments catalog', () => {
        const wrapper = mount(CourseCard, {
            props: { course: baseCourse },
            global: {
                stubs: {
                    Link: linkStub,
                },
            },
            slots: {
                badges: '<span data-testid="custom-badge">15 vacantes disponibles</span>',
                footer: '<button data-testid="custom-action">Ver programación y cupos</button>',
            },
        });

        expect(wrapper.find('[data-testid="custom-badge"]').text()).toBe('15 vacantes disponibles');
        expect(wrapper.find('[data-testid="custom-action"]').text()).toBe('Ver programación y cupos');
    });

    it('renders progress percentage and progress bar when course has progress data', () => {
        const wrapper = mount(CourseCard, {
            props: {
                course: {
                    ...baseCourse,
                    is_enrolled: true,
                    progress: {
                        completed_lessons: 2,
                        total_lessons: 4,
                        percentage: 50,
                    },
                },
            },
            global: {
                stubs: {
                    Link: linkStub,
                },
            },
        });

        expect(wrapper.find('[data-testid="course-card-progress-7"]').exists()).toBe(true);
        expect(wrapper.text()).toContain('2 / 4 (50%)');
    });
});
