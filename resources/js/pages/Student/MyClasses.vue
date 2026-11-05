<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import CourseCard, { type CourseCardItem } from '@/components/courses/CourseCard.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import Button from '@/design-system/Button.vue';
import Select from '@/design-system/Select.vue';
import ProgressBar from '@/components/progress/ProgressBar.vue';
import Badge from '@/design-system/Badge.vue';

export interface StudentClassItem {
    id: number;
    enrollment_id: number;
    offering_id: number | null;
    course_id: number;
    name: string;
    excerpt: string;
    cover_url: string;
    units_count: number;
    group_code: string;
    modality: string;
    starts_on: string | null;
    ends_on: string | null;
    teachers: { id: number; name: string }[];
    status: 'scheduled' | 'in_progress' | 'finished';
    can_access: boolean;
    is_finished: boolean;
    progress: {
        completed_lessons: number;
        total_lessons: number;
        percentage: number;
    };
}

const props = withDefaults(
    defineProps<{
        classes: StudentClassItem[];
        filters?: { status?: string };
    }>(),
    {
        filters: () => ({ status: 'in_progress' }),
    },
);

const selectedStatus = ref<string>(props.filters.status || 'in_progress');

const statusOptions = [
    { value: 'in_progress', label: 'En curso' },
    { value: 'scheduled', label: 'Próximas a empezar' },
    { value: 'finished', label: 'Finalizadas' },
    { value: 'all', label: 'Todas las clases' },
];

const filteredClasses = computed(() => {
    if (selectedStatus.value === 'all' || selectedStatus.value === '') {
        return props.classes;
    }

    return props.classes.filter((item) => item.status === selectedStatus.value);
});

function toCourseCardItem(item: StudentClassItem): CourseCardItem {
    return {
        id: item.course_id,
        name: item.name,
        excerpt: item.excerpt,
        cover_url: item.cover_url,
        units_count: item.units_count,
        teachers: item.teachers,
    };
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return '';
    const parts = dateStr.split('-');
    if (parts.length === 3) {
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }
    return dateStr;
}
</script>

<template>
    <Head title="Mis clases" />
    <AppLayout>
        <header class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Mis clases</h1>
                <p class="mt-1 text-sm text-text-muted">Consulta tus clases matriculadas, horarios y materiales de estudio.</p>
            </div>
            <div class="flex items-center gap-3">
                <Link href="/my/progress" class="text-sm font-medium text-primary hover:underline">
                    Mi progreso
                </Link>
            </div>
        </header>

        <!-- FILTRO POR ESTADO Y CATÁLOGO PRINCIPAL -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex w-full sm:w-64 flex-col gap-1">
                <label for="filter-status" class="text-xs font-medium text-text-muted">Filtrar clases por estado</label>
                <Select
                    id="filter-status"
                    v-model="selectedStatus"
                    :options="statusOptions"
                />
            </div>
            <div class="text-xs text-text-muted">
                Mostrando {{ filteredClasses.length }} de {{ classes.length }} {{ classes.length === 1 ? 'clase' : 'clases' }}
            </div>
        </div>

        <div v-if="classes.length === 0" class="rounded-card border border-border bg-panel">
            <EmptyState
                title="Aún no tienes clases matriculadas"
                description="Tus clases y materias aparecerán aquí cuando tengas una inscripción activa."
            />
        </div>
        <div v-else-if="filteredClasses.length === 0" class="rounded-card border border-border bg-panel">
            <EmptyState
                title="No hay clases en este estado"
                description="Prueba seleccionando otro estado en el filtro superior."
            />
        </div>
        <ul v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <li v-for="item in filteredClasses" :key="item.id" class="flex flex-col">
                <CourseCard :course="toCourseCardItem(item)">
                    <template #badges>
                        <Badge variant="neutral">
                            Grupo {{ item.group_code }}
                        </Badge>
                        <Badge
                            :variant="
                                item.status === 'in_progress'
                                    ? 'success'
                                    : item.status === 'scheduled'
                                      ? 'warning'
                                      : 'neutral'
                            "
                        >
                            {{
                                item.status === 'in_progress'
                                    ? 'En curso'
                                    : item.status === 'scheduled'
                                      ? `Inicia el ${formatDate(item.starts_on)}`
                                      : 'Finalizada'
                            }}
                        </Badge>
                    </template>

                    <template #meta>
                        <div class="mt-2 space-y-1.5">
                            <div class="flex items-center justify-between text-xs text-text-muted">
                                <span>Avance</span>
                                <span class="font-medium text-text">
                                    {{ item.progress.completed_lessons }} / {{ item.progress.total_lessons }} ({{ item.progress.percentage }}%)
                                </span>
                            </div>
                            <ProgressBar :percentage="item.progress.percentage" />
                            <div class="flex items-center justify-between text-xs text-text-muted pt-1">
                                <span v-if="item.starts_on">📅 Inicio: {{ formatDate(item.starts_on) }}</span>
                                <span v-if="item.ends_on">🏁 Fin: {{ formatDate(item.ends_on) }}</span>
                            </div>
                        </div>
                    </template>

                    <template #footer>
                        <span class="text-xs font-semibold text-primary">
                            {{ item.progress.percentage }}% de avance
                        </span>

                        <!-- Si no puede acceder porque no ha iniciado -->
                        <div v-if="!item.can_access" class="flex items-center gap-2">
                            <Button size="sm" variant="secondary" disabled>
                                Inicia el {{ formatDate(item.starts_on) }}
                            </Button>
                        </div>
                        <!-- Si ya inició o finalizó -->
                        <Link v-else :href="`/my/courses/${item.course_id}`">
                            <Button size="sm" :variant="item.is_finished ? 'secondary' : 'primary'">
                                {{ item.is_finished ? 'Ver contenido (Consulta)' : 'Entrar a Clase' }}
                            </Button>
                        </Link>
                    </template>
                </CourseCard>
            </li>
        </ul>
    </AppLayout>
</template>
