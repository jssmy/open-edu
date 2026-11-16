import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import CourseViewer from '@/pages/Study/CourseViewer.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div><slot /></div>' },
    Link: {
        props: ['href'],
        template: '<a :href="href"><slot /></a>',
    },
    router: { post: vi.fn(), get: vi.fn() },
    usePage: () => ({ props: { errors: {} } }),
}));

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: { template: '<div><slot /></div>' },
}));

describe('CourseViewer Resume and Completion Controls', () => {
    const createCourse = (lesson1Completed: boolean, lesson2Completed: boolean, lesson3Completed: boolean) => ({
        id: 10,
        name: 'Curso de Arquitectura',
        description: null,
        intro_video_embed_url: null,
        units: [
            {
                id: 1,
                title: 'Unidad 1: Fundamentos',
                position: 1,
                lessons: [
                    {
                        id: 101,
                        title: 'Lección 1: Introducción',
                        position: 1,
                        content_type: 'text' as const,
                        content: null,
                        is_completed: lesson1Completed,
                    },
                    {
                        id: 102,
                        title: 'Lección 2: Conceptos Clave',
                        position: 2,
                        content_type: 'text' as const,
                        content: null,
                        is_completed: lesson2Completed,
                    },
                    {
                        id: 103,
                        title: 'Lección 3: Práctica Avanzada',
                        position: 3,
                        content_type: 'text' as const,
                        content: null,
                        is_completed: lesson3Completed,
                    },
                ],
            },
        ],
        progress: {
            completed_lessons: (lesson1Completed ? 1 : 0) + (lesson2Completed ? 1 : 0) + (lesson3Completed ? 1 : 0),
            total_lessons: 3,
            percentage: Math.round(
                (((lesson1Completed ? 1 : 0) + (lesson2Completed ? 1 : 0) + (lesson3Completed ? 1 : 0)) / 3) * 100,
            ),
        },
        assessments: [],
    });

    it('resumes at the last completed lesson (slide 2) when lessons 1 and 2 are completed for teacher', () => {
        const wrapper = mount(CourseViewer, {
            props: {
                course: createCourse(true, true, false),
                isTeacherView: true,
                canMarkComplete: true,
            },
        });

        // Debe posicionarse en la lección 2 (la última completada)
        expect(wrapper.text()).toContain('Lección 2: Conceptos Clave');
        expect(wrapper.text()).toContain('Diapositiva 2 / 3');
        expect(wrapper.text()).toContain('Dictada');
        expect(wrapper.text()).toContain('Lección dictada');
        expect(wrapper.text()).toContain('Desmarcar');
    });

    it('resumes at the last completed lesson (slide 2) when lessons 1 and 2 are completed for student', () => {
        const wrapper = mount(CourseViewer, {
            props: {
                course: createCourse(true, true, false),
                isTeacherView: false,
                canMarkComplete: true,
            },
        });

        // Debe posicionarse en la lección 2
        expect(wrapper.text()).toContain('Lección 2: Conceptos Clave');
        expect(wrapper.text()).toContain('Diapositiva 2 / 3');
        expect(wrapper.text()).toContain('Completada');
        expect(wrapper.text()).toContain('Lección completada');
    });

    it('starts at slide 1 when no lessons have been completed', () => {
        const wrapper = mount(CourseViewer, {
            props: {
                course: createCourse(false, false, false),
                isTeacherView: true,
                canMarkComplete: true,
            },
        });

        expect(wrapper.text()).toContain('Lección 1: Introducción');
        expect(wrapper.text()).toContain('Diapositiva 1 / 3');
        expect(wrapper.text()).toContain('Marcar completada');
    });

    it('shows guided progress notice when student is in scheduled class (canMarkComplete = false)', () => {
        const wrapper = mount(CourseViewer, {
            props: {
                course: createCourse(false, false, false),
                isTeacherView: false,
                canMarkComplete: false,
            },
        });

        expect(wrapper.text()).toContain('Avance guiado por el docente');
        expect(wrapper.text()).not.toContain('Marcar completada');
    });
});

