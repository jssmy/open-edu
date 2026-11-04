<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/design-system/Badge.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import ScheduleCalendar from '@/design-system/ScheduleCalendar.vue';
import Table from '@/design-system/Table.vue';
import type { CalendarScheduleBlock } from '@/composables/useScheduleCalendar';

interface SessionRow {
    id: number;
    title: string;
    session_date: string;
    starts_at: string;
    ends_at: string;
    classroom_name: string | null;
    meeting_url: string | null;
    status: string;
}

interface ScheduleRow {
    id: number;
    course_name: string;
    group_code: string;
    starts_on: string;
    ends_on: string;
    modality: 'in_person' | 'virtual' | 'hybrid';
    sessions: SessionRow[];
}

defineProps<{
    schedules: ScheduleRow[];
    calendarBlocks: CalendarScheduleBlock[];
}>();

const modalityLabels: Record<string, string> = {
    in_person: 'Presencial',
    virtual: 'Virtual',
    hybrid: 'Híbrida',
};

const columns = [
    { key: 'course', label: 'Curso y grupo' },
    { key: 'dates', label: 'Fechas' },
    { key: 'location', label: 'Modalidad' },
    { key: 'sessions', label: 'Sesiones programadas' },
];
</script>

<template>
    <Head title="Mi horario" />
    <AppLayout>
        <div class="mb-6">
            <h1 class="text-xl font-semibold">Mi horario de clases</h1>
            <p class="text-sm text-text-muted">Consulta tus grupos, sesiones programadas y aulas asignadas.</p>
        </div>

        <div class="mb-6">
            <ScheduleCalendar :blocks="calendarBlocks" :editable="false" />
        </div>

        <Table :columns="columns" :rows="schedules as unknown as Record<string, unknown>[]">
            <template #cell-course="{ row }">
                <p class="font-semibold">{{ row.course_name }}</p>
                <span class="text-xs text-text-muted">{{ row.group_code }}</span>
            </template>
            <template #cell-dates="{ row }">
                <span class="text-xs">{{ row.starts_on }} al {{ row.ends_on }}</span>
            </template>
            <template #cell-location="{ row }">
                <Badge :variant="row.modality === 'virtual' ? 'neutral' : 'success'">
                    {{ modalityLabels[String(row.modality)] ?? row.modality }}
                </Badge>
            </template>
            <template #cell-sessions="{ row }">
                <ul class="space-y-1 text-xs">
                    <li
                        v-for="(session, idx) in row.sessions as SessionRow[]"
                        :key="idx"
                    >
                        <strong>{{ session.session_date }}</strong> ({{ session.starts_at }} – {{ session.ends_at }})
                        <span v-if="session.classroom_name" class="text-text-muted">
                            [{{ session.classroom_name }}]
                        </span>
                    </li>
                </ul>
            </template>
            <template #empty>
                <EmptyState
                    title="Aún no tienes sesiones programadas"
                    description="Tus cursos y sesiones aparecerán aquí cuando se definan los horarios."
                />
            </template>
        </Table>
    </AppLayout>
</template>
