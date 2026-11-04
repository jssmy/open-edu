import { computed, ref } from 'vue';

export interface CalendarScheduleBlock {
    id?: number;
    scheduleId: number;
    enrollmentId?: number;
    sessionId?: number;
    blockIndex: number;
    title?: string;
    groupCode?: string;
    courseName: string;
    teacherId?: number;
    teacherName: string;
    modality: 'in_person' | 'virtual' | 'hybrid';
    classroomName: string | null;
    meetingUrl?: string | null;
    sessionDate?: string; // YYYY-MM-DD
    dayOfWeek: number; // 1 = Lunes .. 7 = Domingo
    startsAt: string; // HH:MM
    endsAt: string; // HH:MM
    status?: string;
    readOnly?: boolean;
}

export interface TeacherColorLegendItem {
    key: string;
    teacherName: string;
    backgroundColor: string;
    borderColor: string;
}

const TEACHER_COLOR_PALETTE: ReadonlyArray<{ backgroundColor: string; borderColor: string }> = [
    { backgroundColor: '#1d4ed8', borderColor: '#1e40af' },
    { backgroundColor: '#047857', borderColor: '#065f46' },
    { backgroundColor: '#b45309', borderColor: '#92400e' },
    { backgroundColor: '#7c3aed', borderColor: '#6d28d9' },
    { backgroundColor: '#be123c', borderColor: '#9f1239' },
    { backgroundColor: '#0e7490', borderColor: '#155e75' },
    { backgroundColor: '#4338ca', borderColor: '#3730a3' },
    { backgroundColor: '#a21caf', borderColor: '#86198f' },
];

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

export function formatIsoDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

export function useScheduleCalendar(blocks: () => CalendarScheduleBlock[], editable = true) {
    const currentView = ref<'timeGridWeek' | 'timeGridDay' | 'dayGridMonth'>('timeGridWeek');

    const teacherColorMap = computed(() => {
        const map = new Map<string, TeacherColorLegendItem>();
        for (const block of blocks()) {
            const key = block.teacherId ? `id:${block.teacherId}` : `name:${block.teacherName}`;
            if (!map.has(key)) {
                const paletteColor = TEACHER_COLOR_PALETTE[map.size % TEACHER_COLOR_PALETTE.length];
                map.set(key, {
                    key,
                    teacherName: block.teacherName || 'Sin docente',
                    backgroundColor: paletteColor.backgroundColor,
                    borderColor: paletteColor.borderColor,
                });
            }
        }
        return map;
    });

    const teacherLegend = computed<TeacherColorLegendItem[]>(() => Array.from(teacherColorMap.value.values()));

    const calendarEvents = computed(() =>
        blocks().map((block) => {
            const dateStr = block.sessionDate ?? dateForIsoDay(block.dayOfWeek);
            const locationLabel =
                block.modality === 'virtual'
                    ? 'Aula: Virtual'
                    : block.modality === 'hybrid'
                      ? `Aula: Híbrido${block.classroomName ? ` · ${block.classroomName}` : ''}`
                      : block.classroomName
                        ? `Aula: ${block.classroomName}`
                        : 'Aula: Sin asignar';
            const groupLabel = block.groupCode ? ` (${block.groupCode})` : '';
            const courseLabel = `${block.courseName}${groupLabel}`;
            const teacherDisplay = block.teacherName || 'Sin docente';
            const teacherLabel = `Docente: ${teacherDisplay}`;
            const teacherKey = block.teacherId ? `id:${block.teacherId}` : `name:${block.teacherName}`;
            const color = teacherColorMap.value.get(teacherKey) ?? TEACHER_COLOR_PALETTE[0];
            return {
                id: `${block.scheduleId}-${block.blockIndex}`,
                title: `${courseLabel}\n${teacherLabel}\n${locationLabel}`,
                start: `${dateStr}T${block.startsAt}:00`,
                end: `${dateStr}T${block.endsAt}:00`,
                editable: editable && !block.readOnly,
                color: color.backgroundColor,
                contrastColor: '#ffffff',
                backgroundColor: color.backgroundColor,
                borderColor: color.borderColor,
                textColor: '#ffffff',
                extendedProps: {
                    scheduleId: block.scheduleId,
                    enrollmentId: block.enrollmentId,
                    sessionId: block.sessionId,
                    blockIndex: block.blockIndex,
                    sessionDate: dateStr,
                    courseLabel,
                    teacherName: teacherDisplay,
                    teacherLabel,
                    classroomName: block.classroomName,
                    locationLabel,
                    readOnly: Boolean(block.readOnly),
                },
            };
        }),
    );

    const initialDate = computed(() => {
        const list = blocks();
        const firstDated = list.find((b) => Boolean(b.sessionDate));
        return firstDated?.sessionDate ?? REFERENCE_MONDAY;
    });

    return {
        currentView,
        referenceMonday: REFERENCE_MONDAY,
        initialDate,
        calendarEvents,
        teacherLegend,
    };
}
