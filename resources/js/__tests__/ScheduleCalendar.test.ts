import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import ScheduleCalendar from '@/design-system/ScheduleCalendar.vue';
import type { CalendarScheduleBlock } from '@/composables/useScheduleCalendar';

let capturedOptions: Record<string, unknown> = {};

vi.mock('@fullcalendar/vue3', () => ({
    default: {
        name: 'FullCalendar',
        props: ['options'],
        setup(props: { options: Record<string, unknown> }) {
            capturedOptions = props.options;
            return {};
        },
        template: '<div data-testid="fc-stub">{{ options.events.length }}:{{ options.initialView }}</div>',
    },
}));

const sampleBlocks: CalendarScheduleBlock[] = [
    {
        scheduleId: 10,
        blockIndex: 0,
        courseName: 'Matemáticas',
        teacherName: 'Ana Pérez',
        modality: 'in_person',
        classroomName: 'Aula 101',
        dayOfWeek: 1,
        startsAt: '08:00',
        endsAt: '10:00',
    },
];

describe('ScheduleCalendar', () => {
    it('renderiza los eventos y permite cambiar entre vista semanal y diaria', async () => {
        const wrapper = mount(ScheduleCalendar, {
            props: { blocks: sampleBlocks, editable: true },
        });

        expect(wrapper.get('[data-testid="fc-stub"]').text()).toBe('1:timeGridWeek');

        await wrapper.findAll('button')[1].trigger('click');
        expect(wrapper.get('[data-testid="fc-stub"]').text()).toBe('1:timeGridDay');
    });

    it('emite select y move a partir de los callbacks de FullCalendar', () => {
        const wrapper = mount(ScheduleCalendar, {
            props: { blocks: sampleBlocks, editable: true },
        });

        const selectFn = capturedOptions.select as (arg: { start: Date; end: Date }) => void;
        selectFn({
            start: new Date(2026, 10, 2, 8, 0),
            end: new Date(2026, 10, 2, 10, 0),
        });

        expect(wrapper.emitted('select')?.[0]?.[0]).toEqual({
            dayOfWeek: 1,
            startsAt: '08:00',
            endsAt: '10:00',
        });

        const revert = vi.fn();
        const dropFn = capturedOptions.eventDrop as (arg: unknown) => void;
        dropFn({
            event: {
                start: new Date(2026, 10, 3, 10, 0),
                end: new Date(2026, 10, 3, 12, 0),
                extendedProps: { scheduleId: 10, blockIndex: 0 },
            },
            revert,
        });

        expect(wrapper.emitted('move')?.[0]?.[0]).toEqual({
            scheduleId: 10,
            blockIndex: 0,
            dayOfWeek: 2,
            startsAt: '10:00',
            endsAt: '12:00',
            revert,
        });
    });
});
