import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h, type PropType } from 'vue';
import { mount } from '@vue/test-utils';
import CourseContentEditor from '@/components/courses/CourseContentEditor.vue';

const { archiveLesson, removeLesson, reorderLessons, reorderUnits, deleteRequest, requestAlertDialog } = vi.hoisted(() => ({
    archiveLesson: vi.fn(),
    removeLesson: vi.fn(),
    reorderLessons: vi.fn(),
    reorderUnits: vi.fn(),
    deleteRequest: vi.fn(),
    requestAlertDialog: vi.fn(),
}));

vi.mock('@/services/courses', () => ({
    archiveLesson,
    removeLesson,
    reorderLessons,
    reorderUnits,
}));

vi.mock('@/composables/useAlertDialog', () => ({ requestAlertDialog }));

vi.mock('@inertiajs/vue3', async () => {
    const vue = await import('vue');

    return {
        router: { delete: deleteRequest },
        Link: vue.defineComponent({
            name: 'Link',
            setup(_, { slots }) {
                return () => vue.h('a', slots.default?.());
            },
        }),
        useForm: () =>
            vue.reactive({
                title: '',
                position: 1,
                errors: {},
                processing: false,
                post: vi.fn(),
                reset: vi.fn(),
            }),
    };
});

const DraggableStub = defineComponent({
    name: 'Draggable',
    props: {
        modelValue: {
            type: Array as PropType<Record<string, unknown>[]>,
            default: () => [],
        },
    },
    setup(props, { slots }) {
        return () =>
            h(
                'div',
                props.modelValue.map((element, index) => slots.item?.({ element, index })),
            );
    },
});

describe('CourseContentEditor', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        requestAlertDialog.mockResolvedValue(false);
    });

    it('asks before deleting a lesson and only deletes after confirmation', async () => {
        const wrapper = mount(CourseContentEditor, {
            props: {
                courseId: 2,
                canManageContent: true,
                units: [
                    {
                        id: 3,
                        title: 'Unidad 1',
                        position: 1,
                        lessons: [
                            {
                                id: 4,
                                title: 'Lección 1',
                                position: 1,
                                content_type: 'text',
                                content: 'Contenido',
                                is_archived: false,
                            },
                        ],
                    },
                ],
            },
            global: { stubs: { Draggable: DraggableStub } },
        });
        const deleteButtons = wrapper.findAll('button').filter((button) => button.text() === 'Eliminar');
        expect(deleteButtons).toHaveLength(2);
        await deleteButtons[1]?.trigger('click');

        expect(requestAlertDialog).toHaveBeenCalledWith({
            variant: 'danger',
            title: '¿Eliminar esta lección?',
            message: 'Esta acción no se puede deshacer.',
            confirmLabel: 'Eliminar lección',
        });
        expect(removeLesson).not.toHaveBeenCalled();

        requestAlertDialog.mockResolvedValue(true);
        await deleteButtons[1]?.trigger('click');

        expect(removeLesson).toHaveBeenCalledWith(4);
        expect(requestAlertDialog.mock.invocationCallOrder[1]).toBeLessThan(
            removeLesson.mock.invocationCallOrder[0] ?? Number.POSITIVE_INFINITY,
        );

        wrapper.unmount();
    });
});
