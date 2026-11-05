import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import ScheduleCalendar from '@/design-system/ScheduleCalendar.vue';
import type { CalendarScheduleBlock } from '@/composables/useScheduleCalendar';

let capturedOptions: Record<string, unknown> = {};

const mockApi = {
    prev: vi.fn(),
    next: vi.fn(),
    today: vi.fn(),
};

vi.mock('@fullcalendar/vue3', () => ({
    default: {
        name: 'FullCalendar',
        props: ['options'],
        setup(props: { options: Record<string, unknown> }, { expose }: { expose: (obj: unknown) => void }) {
            capturedOptions = props.options;
            expose({
                getApi: () => mockApi,
            });
            return {
                getApi: () => mockApi,
            };
        },
        template: `
            <div data-testid="fc-stub">
                <span data-testid="fc-summary">{{ options.events.length }}:{{ options.initialView }}</span>
                <div v-for="ev in options.events" :key="ev.id" class="fc-event-stub">
                    <slot name="eventContent" :event="ev" :timeText="'08:00–10:00'" />
                </div>
            </div>
        `,
    },
}));

const sampleBlocks: CalendarScheduleBlock[] = [
    {
        scheduleId: 10,
        sessionId: 100,
        blockIndex: 0,
        title: 'Álgebra I',
        groupCode: 'GRP-01',
        courseName: 'Matemáticas',
        teacherId: 1,
        teacherName: 'Ana Pérez',
        modality: 'in_person',
        classroomName: 'Aula 101',
        sessionDate: '2026-11-02',
        dayOfWeek: 1,
        startsAt: '08:00',
        endsAt: '10:00',
    },
    {
        scheduleId: 11,
        sessionId: 101,
        blockIndex: 0,
        title: 'Física I',
        groupCode: 'GRP-01',
        courseName: 'Física',
        teacherId: 2,
        teacherName: 'Carlos Gómez',
        modality: 'in_person',
        classroomName: 'Lab 202',
        sessionDate: '2026-11-03',
        dayOfWeek: 2,
        startsAt: '10:00',
        endsAt: '12:00',
    },
];

describe('ScheduleCalendar', () => {
    it('renderiza los eventos y permite cambiar entre vista semanal y diaria', async () => {
        const wrapper = mount(ScheduleCalendar, {
            props: { blocks: sampleBlocks, editable: true },
        });

        expect(wrapper.get('[data-testid="fc-summary"]').text()).toBe('2:timeGridWeek');

        const buttons = wrapper.findAll('button');
        // El botón Diaria tiene el texto "Diaria"
        const diariaBtn = buttons.find((b) => b.text().includes('Diaria'));
        expect(diariaBtn).toBeDefined();
        await diariaBtn!.trigger('click');
        expect(wrapper.get('[data-testid="fc-summary"]').text()).toBe('2:timeGridDay');
    });

    it('permite navegar con anterior, siguiente y hoy y muestra el título del período', async () => {
        const wrapper = mount(ScheduleCalendar, {
            props: { blocks: sampleBlocks, editable: true },
        });

        // Trigger datesSet from captured options to simulate FullCalendar initial render
        const datesSet = capturedOptions.datesSet as (arg: { view: { title: string } }) => void;
        datesSet({ view: { title: 'noviembre de 2026' } });
        await wrapper.vm.$nextTick();

        expect(wrapper.get('[data-testid="calendar-period-title"]').text()).toBe('noviembre de 2026');

        await wrapper.get('[data-testid="calendar-prev-button"]').trigger('click');
        expect(mockApi.prev).toHaveBeenCalled();

        await wrapper.get('[data-testid="calendar-next-button"]').trigger('click');
        expect(mockApi.next).toHaveBeenCalled();

        await wrapper.get('[data-testid="calendar-today-button"]').trigger('click');
        expect(mockApi.today).toHaveBeenCalled();
    });

    it('asigna un color diferente por docente y muestra información del docente y aula en cada ítem del calendario', () => {
        const wrapper = mount(ScheduleCalendar, {
            props: { blocks: sampleBlocks, editable: true },
        });

        const events = capturedOptions.events as Array<Record<string, unknown>>;
        expect(events).toHaveLength(2);
        expect(events[0].color).not.toBe(events[1].color);
        expect(events[0].backgroundColor).not.toBe(events[1].backgroundColor);
        expect(events[0].title).toContain('Docente: Ana Pérez');
        expect(events[0].title).toContain('Aula: Aula 101');
        expect(events[1].title).toContain('Docente: Carlos Gómez');
        expect(events[1].title).toContain('Aula: Lab 202');

        const eventItems = wrapper.findAll('[data-testid="calendar-event-item"]');
        expect(eventItems).toHaveLength(2);
        expect(eventItems[0].text()).toContain('Matemáticas (GRP-01)');
        expect(eventItems[0].text()).toContain('Docente: Ana Pérez');
        expect(eventItems[0].text()).toContain('Aula: Aula 101');
        expect(eventItems[1].text()).toContain('Física (GRP-01)');
        expect(eventItems[1].text()).toContain('Docente: Carlos Gómez');
        expect(eventItems[1].text()).toContain('Aula: Lab 202');

        expect(wrapper.find('[data-testid="teacher-color-legend"]').exists()).toBe(false);
    });

    it('emite select, move y eventClick a partir de los callbacks de FullCalendar', () => {
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

        const clickFn = capturedOptions.eventClick as (arg: unknown) => void;
        clickFn({
            event: {
                extendedProps: { scheduleId: 10, enrollmentId: 42 },
            },
        });

        expect(wrapper.emitted('eventClick')?.[0]?.[0]).toBe(10);
        expect(wrapper.emitted('eventClick')?.[0]?.[1]).toEqual({ scheduleId: 10, enrollmentId: 42 });

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
