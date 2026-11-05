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
import type { CourseStatus } from '@/services/courses';

export interface MyCourseProgress {
    completed_lessons: number;
    total_lessons: number;
    percentage: number;
    status: 'not_started' | 'in_progress' | 'completed';
}

export interface MyCourseItem {
    id: number;
    name: string;
    excerpt?: string | null;
    description?: string | null;
    cover_url: string;
    units_count: number;
    status: CourseStatus;
    teachers: { id: number; name: string }[];
    progress: MyCourseProgress;
}

const props = withDefaults(
    defineProps<{
        courses: MyCourseItem[];
        filters?: { status?: string };
    }>(),
    {
        filters: () => ({ status: 'in_progress' }),
    },
);

const selectedStatus = ref<string>(props.filters.status || 'in_progress');

const statusOptions = [
    { value: 'in_progress', label: 'En proceso' },
    { value: 'completed', label: 'Completados' },
    { value: 'not_started', label: 'No iniciados' },
    { value: 'all', label: 'Todos los cursos' },
];

const filteredCourses = computed(() => {
    if (selectedStatus.value === 'all' || selectedStatus.value === '') {
        return props.courses;
    }

    return props.courses.filter((course) => {
        if (selectedStatus.value === 'completed') {
            return course.progress.percentage >= 100;
        }
        if (selectedStatus.value === 'in_progress') {
            return course.progress.percentage > 0 && course.progress.percentage < 100;
        }
        if (selectedStatus.value === 'not_started') {
            return course.progress.percentage === 0;
        }
        return true;
    });
});

function toCourseCardItem(course: MyCourseItem): CourseCardItem {
    return {
        id: course.id,
        name: course.name,
        excerpt: course.excerpt,
        description: course.description,
        cover_url: course.cover_url,
        units_count: course.units_count,
        status: course.status,
        teachers: course.teachers,
    };
}
</script>

<template>
    <Head title="Mis cursos" />
    <AppLayout>
        <header class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Mis cursos</h1>
                <p class="mt-1 text-sm text-text-muted">Continúa donde dejaste tu aprendizaje.</p>
            </div>
            <div class="flex items-center gap-3">
                <Link href="/my/progress" class="text-sm font-medium text-primary hover:underline">
                    Mi progreso
                </Link>
            </div>
        </header>

        <!-- Filtro por estado -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div class="flex w-full sm:w-64 flex-col gap-1">
                <label for="filter-status" class="text-xs font-medium text-text-muted">Filtrar por estado</label>
                <Select
                    id="filter-status"
                    v-model="selectedStatus"
                    :options="statusOptions"
                />
            </div>
            <div class="text-xs text-text-muted">
                Mostrando {{ filteredCourses.length }} de {{ courses.length }} {{ courses.length === 1 ? 'curso' : 'cursos' }}
            </div>
        </div>

        <div v-if="courses.length === 0" class="rounded-card border border-border bg-panel">
            <EmptyState
                title="Aún no tienes cursos disponibles"
                description="Tus cursos publicados aparecerán aquí cuando tengas una inscripción activa."
            />
        </div>
        <div v-else-if="filteredCourses.length === 0" class="rounded-card border border-border bg-panel">
            <EmptyState
                title="No hay cursos en este estado"
                description="Prueba seleccionando otro estado en el filtro superior."
            />
        </div>
        <ul v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <li v-for="course in filteredCourses" :key="course.id" class="flex flex-col">
                <CourseCard :course="toCourseCardItem(course)">
                    <template #badges>
                        <Badge
                            :variant="
                                course.progress.percentage >= 100
                                    ? 'success'
                                    : course.progress.percentage > 0
                                      ? 'warning'
                                      : 'neutral'
                            "
                        >
                            {{
                                course.progress.percentage >= 100
                                    ? 'Completado'
                                    : course.progress.percentage > 0
                                      ? 'En proceso'
                                      : 'No iniciado'
                            }}
                        </Badge>
                    </template>

                    <template #meta>
                        <div class="mt-2 space-y-1.5">
                            <div class="flex items-center justify-between text-xs text-text-muted">
                                <span>Avance</span>
                                <span class="font-medium text-text">
                                    {{ course.progress.completed_lessons }} / {{ course.progress.total_lessons }} ({{ course.progress.percentage }}%)
                                </span>
                            </div>
                            <ProgressBar :percentage="course.progress.percentage" />
                        </div>
                    </template>

                    <template #footer>
                        <span class="text-xs font-semibold text-primary">
                            {{ course.progress.percentage }}% de avance
                        </span>
                        <Link :href="`/my/courses/${course.id}`">
                            <Button size="sm" variant="primary">
                                {{ course.progress.percentage > 0 ? 'Continuar curso' : 'Iniciar curso' }}
                            </Button>
                        </Link>
                    </template>
                </CourseCard>
            </li>
        </ul>
    </AppLayout>
</template>
