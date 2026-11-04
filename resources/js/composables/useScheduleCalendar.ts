import { computed, ref } from 'vue';

export interface CalendarScheduleBlock {
    id?: number;
    scheduleId: number;
    blockIndex: number;
    courseName: string;
    teacherName: string;
    modality: 'in_person' | 'virtual';
    classroomName: string | null;
    dayOfWeek: number; // 1 = Lunes .. 7 = Domingo
    startsAt: string; // HH:MM
    endsAt: string; // HH:MM
    readOnly?: boolean;
}

export interface CalendarSelection {
    dayOfWeek: number;
    startsAt: string;
    endsAt: string;
}

export interface CalendarBlockMove {
    scheduleId: number;
    blockIndex: number;
    dayOfWeek: number;
    startsAt: string;
    endsAt: string;
    revert: () => void;
}

const REFERENCE_MONDAY = '2026-11-02'; // 2026-11-02 es lunes

export function dateForIsoDay(dayOfWeek: number): string {
    const day = Math.min(7, Math.max(1, dayOfWeek));
    const dateNumber = 1 + day;
    return `2026-11-${String(dateNumber).padStart(2, '0')}`;
}

export function isoDayFromDate(date: Date): number {
    const jsDay = date.getDay();
    return jsDay === 0 ? 7 : jsDay;
}

export function formatTimeFromDate(date: Date): string {
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
}

export function useScheduleCalendar(blocks: () => CalendarScheduleBlock[], editable = true) {
    const currentView = ref<'timeGridWeek' | 'timeGridDay'>('timeGridWeek');

    const calendarEvents = computed(() =>
        blocks().map((block) => {
            const dateStr = dateForIsoDay(block.dayOfWeek);
            const locationLabel = block.modality === 'virtual' ? 'Virtual' : (block.classroomName ?? 'Sin aula');
            return {
                id: `${block.scheduleId}-${block.blockIndex}`,
                title: `${block.courseName} · ${block.teacherName} (${locationLabel})`,
                start: `${dateStr}T${block.startsAt}:00`,
                end: `${dateStr}T${block.endsAt}:00`,
                editable: editable && !block.readOnly,
                extendedProps: {
                    scheduleId: block.scheduleId,
                    blockIndex: block.blockIndex,
                    readOnly: Boolean(block.readOnly),
                },
            };
        }),
    );

    return {
        currentView,
        referenceMonday: REFERENCE_MONDAY,
        calendarEvents,
    };
}

