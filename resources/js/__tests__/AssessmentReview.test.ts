import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import AssessmentReview from '@/pages/Assessments/Review.vue';

vi.mock('@/layouts/AppLayout.vue', () => ({
    default: {
        name: 'AppLayout',
        template: '<div><slot /></div>',
    },
}));

vi.mock('@/components/editor/RichTextContent.vue', () => ({
    default: {
        name: 'RichTextContent',
        props: ['content', 'label'],
        template: '<div data-testid="rich-text-content">Texto enunciado</div>',
    },
}));

vi.mock('@inertiajs/vue3', () => ({
    Head: { template: '<div />' },
    Link: {
        props: ['href'],
        template: '<a :href="href"><slot /></a>',
    },
}));

describe('AssessmentReview Page', () => {
    const mockProps = {
        assessment: {
            id: 5,
            title: 'Examen de Álgebra',
            instructions: null,
            total_score: 20,
            passing_score: 11,
            course_name: 'Matemáticas',
        },
        student: {
            id: 2,
            name: 'Juan Pérez',
            email: 'juan@test.com',
        },
        attempt: {
            id: 10,
            attempt_number: 1,
            status: 'completed' as const,
            score: 20,
            passed: true,
            started_at: '2026-11-04T10:00:00Z',
            submitted_at: '2026-11-04T10:45:00Z',
        },
        questions: [
            {
                id: 1,
                statement: { type: 'doc' as const, content: [] },
                points: 20,
                choices: [
                    { id: 101, text: 'Opción A (Correcta)', is_correct: true },
                    { id: 102, text: 'Opción B (Incorrecta)', is_correct: false },
                ],
                student_answer: {
                    choice_id: 101,
                    is_correct: true,
                },
            },
        ],
        isStudent: false,
        canContinue: false,
    };

    it('renders assessment questions, choices and student answer in read-only mode', () => {
        const wrapper = mount(AssessmentReview, {
            props: mockProps,
        });

        expect(wrapper.text()).toContain('Examen de Álgebra');
        expect(wrapper.text()).toContain('Juan Pérez');
        expect(wrapper.text()).toContain('Pregunta 1');
        expect(wrapper.text()).toContain('Opción A (Correcta)');
        expect(wrapper.text()).toContain('Respuesta correcta');
        expect(wrapper.text()).toContain('[Respuesta del alumno]');
        expect(wrapper.text()).toContain('+20 pts (Correcta)');

        // No submit or editable form buttons should exist
        expect(wrapper.find('button[type="submit"]').exists()).toBe(false);
    });

    it('renders continue assessment button when student has an in_progress attempt', () => {
        const inProgressProps = {
            ...mockProps,
            isStudent: true,
            canContinue: true,
            attempt: {
                ...mockProps.attempt,
                status: 'in_progress' as const,
                score: 0,
                passed: false,
                submitted_at: null,
            },
        };

        const wrapper = mount(AssessmentReview, {
            props: inProgressProps,
        });

        const continueBtn = wrapper.find('[data-testid="continue-assessment-button"]');
        expect(continueBtn.exists()).toBe(true);
        expect(continueBtn.attributes('href')).toBe('/assessments/5/take');
    });
});
