<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/design-system/Card.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import ProgressBar from '@/components/progress/ProgressBar.vue';

interface Course {
    id: number;
    name: string;
    description: string | null;
    progress: {
        completed_lessons: number;
        total_lessons: number;
        percentage: number;
    };
}

defineProps<{ courses: Course[] }>();
</script>

<template>
    <Head title="Mis cursos" />
    <AppLayout>
        <header class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Mis cursos</h1>
                <p class="mt-2 text-sm text-text-muted">Continúa donde dejaste tu aprendizaje.</p>
            </div>
            <Link href="/my/progress" class="text-sm font-medium text-primary hover:underline">
                Mi progreso
            </Link>
        </header>

        <EmptyState
            v-if="courses.length === 0"
            title="Aún no tienes cursos disponibles"
            description="Tus cursos publicados aparecerán aquí cuando tengas una inscripción activa."
        />
        <ul v-else class="grid gap-4 md:grid-cols-2">
            <li v-for="course in courses" :key="course.id">
                <Card class="flex h-full flex-col gap-4">
                    <div class="flex flex-col gap-1">
                        <h2 class="text-lg font-semibold">
                            <Link :href="`/my/courses/${course.id}`" class="text-primary hover:underline">
                                {{ course.name }}
                            </Link>
                        </h2>
                        <p v-if="course.description" class="text-sm text-text-muted">
                            {{ course.description }}
                        </p>
                    </div>
                    <div class="mt-auto flex flex-col gap-2">
                        <div class="flex justify-between gap-3 text-sm">
                            <span>Lecciones completadas</span>
                            <span
                                >{{ course.progress.completed_lessons }} /
                                {{ course.progress.total_lessons }}</span
                            >
                        </div>
                        <ProgressBar :percentage="course.progress.percentage" />
                        <Link
                            :href="`/my/courses/${course.id}`"
                            class="text-sm font-medium text-primary hover:underline"
                        >
                            Continuar curso
                        </Link>
                    </div>
                </Card>
            </li>
        </ul>
    </AppLayout>
</template>
