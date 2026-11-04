<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/design-system/Badge.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import ScheduleCalendar from '@/design-system/ScheduleCalendar.vue';
import Table from '@/design-system/Table.vue';
import type { CalendarScheduleBlock } from '@/composables/useScheduleCalendar';

interface ScheduleRow {
    id: number;
    course_name: string;
    period_name: string;
    modality: 'in_person' | 'virtual';
    classroom_name: string | null;
    blocks: { day_of_week: number; starts_at: string; ends_at: string }[];
}

defineProps<{
    schedules: ScheduleRow[];
    calendarBlocks: CalendarScheduleBlock[];
}>();

const dayNames: Record<number, string> = {
    1: 'Lunes',
    2: 'Martes',
    3: 'Miércoles',
    4: 'Jueves',
    5: 'Viernes',
    6: 'Sábado',
    7: 'Domingo',
};

const columns = [
    { key: 'course', label: 'Curso' },
    { key: 'period', label: 'Periodo' },
    { key: 'location', label: 'Lugar / Modalidad' },
    { key: 'blocks', label: 'Horario semanal' },
];
</script>

<template>
    <Head title="Mi horario" />
    <AppLayout>
        <div class="mb-6">
            <h1 class="text-xl font-semibold">Mi horario semanal</h1>
            <p class="text-sm text-text-muted">Consulta tus cursos, horarios y aulas asignadas.</p>
        </div>

        <div class="mb-6">
            <ScheduleCalendar :blocks="calendarBlocks" :editable="false" />
        </div>

        <Table :columns="columns" :rows="schedules as unknown as Record<string, unknown>[]">
            <template #cell-course="{ row }">
                <span class="font-semibold">{{ row.course_name }}</span>
            </template>
            <template #cell-period="{ row }">{{ row.period_name }}</template>
            <template #cell-location="{ row }">
                <Badge :variant="row.modality === 'virtual' ? 'neutral' : 'success'">
                    {{ row.modality === 'virtual' ? 'Virtual' : row.classroom_name || 'Presencial' }}
                </Badge>
            </template>
            <template #cell-blocks="{ row }">
                <ul class="space-y-1 text-xs">
                    <li
                        v-for="(block, idx) in row.blocks as {
                            day_of_week: number;
                            starts_at: string;
                            ends_at: string;
                        }[]"
                        :key="idx"
                    >
                        {{ dayNames[block.day_of_week] }}: {{ block.starts_at }} – {{ block.ends_at }}
                    </li>
                </ul>
            </template>
            <template #empty>
                <EmptyState
                    title="Aún no tienes horarios programados"
                    description="Tus cursos programados aparecerán aquí cuando se definan los horarios."
                />
            </template>
        </Table>
    </AppLayout>
</template>

