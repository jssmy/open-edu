<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Badge from '@/design-system/Badge.vue';
import Card from '@/design-system/Card.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import ProgressBar from '@/components/progress/ProgressBar.vue';

interface StudentResult {
    assessment_id: number;
    assessment_title: string;
    attempt_number: number;
    score: number;
    total_score: number;
    passed: boolean;
    submitted_at: string;
}

interface CourseProgress {
    course_id: number;
    course_name: string;
    course_status: string;
    course_deleted: boolean;
    enrollment_status: 'active' | 'suspended' | 'cancelled';
    completed_lessons: number;
    total_lessons: number;
    percentage: number;
    can_study: boolean;
    results: StudentResult[];
}

defineProps<{ courses: CourseProgress[] }>();
</script>

<template>
    <Head title="Mi progreso" />
    <AppLayout>
        <main class="flex flex-col gap-6">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-semibold">Mi progreso</h1>
                <Link href="/my/courses" class="text-sm text-primary hover:underline">Mis cursos</Link>
            </header>

            <EmptyState
                v-if="courses.length === 0"
                title="Aún no tienes cursos"
                description="Tus inscripciones y avances aparecerán aquí."
            />
            <div v-else class="flex flex-col gap-4">
                <Card v-for="course in courses" :key="course.course_id" class="flex flex-col gap-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex flex-col gap-1">
                            <h2 class="font-semibold">{{ course.course_name }}</h2>
                            <Badge
                                :variant="
                                    course.enrollment_status === 'active'
                                        ? 'success'
                                        : course.enrollment_status === 'suspended'
                                          ? 'warning'
                                          : 'neutral'
                                "
                            >
                                {{ course.enrollment_status }}
                            </Badge>
                        </div>
                        <Link
                            v-if="course.can_study"
                            :href="`/my/courses/${course.course_id}`"
                            class="text-sm font-medium text-primary hover:underline"
                        >
                            Continuar curso
                        </Link>
                    </div>
                    <Alert v-if="course.course_deleted" variant="warning">
                        Este curso ya no está disponible; se conserva el historial.
                    </Alert>
                    <div class="flex flex-col gap-2">
                        <div class="flex justify-between gap-3 text-sm">
                            <span
                                >{{ course.completed_lessons }} de {{ course.total_lessons }} lecciones</span
                            >
                            <span>{{ course.percentage }}%</span>
                        </div>
                        <ProgressBar :percentage="course.percentage" />
                    </div>
                    <div v-if="course.results.length > 0" class="flex flex-col gap-2">
                        <h3 class="text-sm font-semibold">Resultados de evaluaciones</h3>
                        <ul class="flex flex-col gap-2">
                            <li
                                v-for="result in course.results"
                                :key="`${result.assessment_id}-${result.attempt_number}`"
                                class="flex flex-wrap items-center justify-between gap-2 text-sm"
                            >
                                <span>{{ result.assessment_title }}</span>
                                <span>{{ result.score }} / {{ result.total_score }}</span>
                                <Badge :variant="result.passed ? 'success' : 'danger'">
                                    {{ result.passed ? 'Aprobado' : 'No aprobado' }}
                                </Badge>
                            </li>
                        </ul>
                    </div>
                </Card>
            </div>
        </main>
    </AppLayout>
</template>
