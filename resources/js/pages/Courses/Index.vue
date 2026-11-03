<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/design-system/Badge.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import { useCourses } from '@/composables/useCourses';

interface CourseRow {
    id: number;
    name: string;
    status: 'draft' | 'published' | 'deactivated';
    teachers: { id: number; name: string }[];
}

defineProps<{ courses: CourseRow[]; canManageCourses: boolean }>();
const { statusLabel, statusVariant } = useCourses();
</script>

<template>
    <Head title="Cursos" />
    <AppLayout>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold">Cursos</h1>
            <Link
                v-if="canManageCourses"
                href="/courses/create"
                class="inline-flex min-h-11 items-center justify-center rounded-control bg-primary px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            >
                Nuevo curso
            </Link>
        </div>

        <EmptyState
            v-if="courses.length === 0"
            title="Aún no hay cursos"
            description="Los cursos disponibles aparecerán aquí."
        />
        <ul v-else class="flex flex-col gap-3">
            <li
                v-for="course in courses"
                :key="course.id"
                class="rounded-card border border-border bg-panel p-4"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <Link :href="`/courses/${course.id}`" class="font-semibold text-primary">
                            {{ course.name }}
                        </Link>
                        <p class="text-sm text-text-muted">
                            {{ course.teachers.map((teacher) => teacher.name).join(', ') }}
                        </p>
                    </div>
                    <Badge :variant="statusVariant(course.status)">{{ statusLabel(course.status) }}</Badge>
                </div>
            </li>
        </ul>
    </AppLayout>
</template>
