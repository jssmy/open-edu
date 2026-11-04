<script setup lang="ts">
import FullCalendar, {
    type CalendarOptions,
    type DateSelectInfo,
    type EventDropInfo,
    type EventResizeDoneInfo,
} from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/vue3/daygrid';
import timeGridPlugin from '@fullcalendar/vue3/timegrid';
import interactionPlugin from '@fullcalendar/vue3/interaction';
import classicTheme from '@fullcalendar/vue3/themes/classic';
import esLocale from '@fullcalendar/vue3/locales/es';
import '@fullcalendar/vue3/skeleton.css';
import '@fullcalendar/vue3/themes/classic/theme.css';
import '@fullcalendar/vue3/themes/classic/palette.css';
import { computed } from 'vue';
import {
    formatTimeFromDate,
    isoDayFromDate,
    useScheduleCalendar,
    type CalendarBlockMove,
    type CalendarScheduleBlock,
    type CalendarSelection,
} from '@/composables/useScheduleCalendar';

const props = withDefaults(
    defineProps<{
        blocks: CalendarScheduleBlock[];
        editable?: boolean;
    }>(),
    {
        editable: true,
    },
);

const emit = defineEmits<{
    select: [selection: CalendarSelection];
    move: [move: CalendarBlockMove];
}>();

const { currentView, referenceMonday, calendarEvents } = useScheduleCalendar(
    () => props.blocks,
    props.editable,
);

function handleSelect(arg: DateSelectInfo): void {
    emit('select', {
        dayOfWeek: isoDayFromDate(arg.start),
        startsAt: formatTimeFromDate(arg.start),
        endsAt: formatTimeFromDate(arg.end),
    });
}

function handleMoveOrResize(arg: EventDropInfo | EventResizeDoneInfo): void {
    const start = arg.event.start;
    const end = arg.event.end;
    if (!start || !end) {
        arg.revert();
        return;
    }

    emit('move', {
        scheduleId: Number(arg.event.extendedProps.scheduleId),
        blockIndex: Number(arg.event.extendedProps.blockIndex),
        dayOfWeek: isoDayFromDate(start),
        startsAt: formatTimeFromDate(start),
        endsAt: formatTimeFromDate(end),
        revert: arg.revert,
    });
}

const calendarOptions = computed<CalendarOptions>(() => ({
    plugins: [classicTheme, dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: currentView.value,
    initialDate: referenceMonday,
    locale: esLocale,
    headerToolbar: false,
    allDaySlot: false,
    slotMinTime: '06:00:00',
    slotMaxTime: '23:00:00',
    dayHeaderFormat: { weekday: 'short' },
    selectable: props.editable,
    editable: props.editable,
    events: calendarEvents.value,
    select: handleSelect,
    eventDrop: handleMoveOrResize,
    eventResize: handleMoveOrResize,
    height: 'auto',
}));

function setView(view: 'timeGridWeek' | 'timeGridDay'): void {
    currentView.value = view;
}
</script>

<template>
    <div
        class="schedule-calendar-wrapper rounded-card border border-border bg-panel p-4 shadow-card"
        data-testid="schedule-calendar"
    >
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm font-semibold text-text">Calendario semanal de horarios</p>
            <div class="flex items-center gap-2" role="group" aria-label="Vista del calendario">
                <button
                    type="button"
                    class="min-h-11 rounded-control border px-3 py-1.5 text-xs font-medium transition"
                    :class="
                        currentView === 'timeGridWeek'
                            ? 'border-primary bg-primary text-white'
                            : 'border-border bg-surface text-text hover:bg-panel'
                    "
                    @click="setView('timeGridWeek')"
                >
                    Semanal
                </button>
                <button
                    type="button"
                    class="min-h-11 rounded-control border px-3 py-1.5 text-xs font-medium transition"
                    :class="
                        currentView === 'timeGridDay'
                            ? 'border-primary bg-primary text-white'
                            : 'border-border bg-surface text-text hover:bg-panel'
                    "
                    @click="setView('timeGridDay')"
                >
                    Diaria
                </button>
            </div>
        </div>
        <FullCalendar :key="currentView" :options="calendarOptions" />
    </div>
</template>

<style scoped>
.schedule-calendar-wrapper {
    --fc-classic-primary: #1e3a5f;
    --fc-classic-primary-foreground: #ffffff;
    --fc-classic-border: #d8e0e8;
    --fc-classic-strong-border: #cbd5e1;
    --fc-classic-background: #ffffff;
    --fc-classic-foreground: #1d2939;
    --fc-classic-muted-foreground: #526174;
    --fc-classic-highlight: rgba(30, 58, 95, 0.12);
    --fc-classic-today: transparent;
}
</style>
