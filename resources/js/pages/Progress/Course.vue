<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
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

interface StudentProgress {
    id: number;
    student_id: number;
    student_name: string;
    student_email: string;
    enrollment_status: 'active' | 'suspended' | 'cancelled';
    completed_lessons: number;
    total_lessons: number;
    percentage: number;
    results: StudentResult[];
}

defineProps<{
    course: { id: number; name: string };
    students: StudentProgress[];
}>();

function statusVariant(status: StudentProgress['enrollment_status']): 'success' | 'warning' | 'neutral' {
    if (status === 'active') {
        return 'success';
    }

    return status === 'suspended' ? 'warning' : 'neutral';
}
</script>

<template>
    <Head :title="`Progreso · ${course.name}`" />
    <AppLayout>
        <main class="flex flex-col gap-6">
            <header class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex flex-col gap-2">
                    <Link :href="`/courses/${course.id}`" class="text-sm text-primary hover:underline">
                        Volver al curso
                    </Link>
                    <h1 class="text-2xl font-semibold">Progreso · {{ course.name }}</h1>
                </div>
                <Link href="/reports/progress" class="text-sm font-medium text-primary hover:underline">
                    Reporte de progreso
                </Link>
            </header>

            <EmptyState
                v-if="students.length === 0"
                title="Aún no hay estudiantes inscritos"
                description="El avance de los estudiantes aparecerá aquí."
            />
            <div v-else class="flex flex-col gap-4">
                <Card v-for="student in students" :key="student.id" class="flex flex-col gap-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold">{{ student.student_name }}</h2>
                            <p class="text-sm text-text-muted">{{ student.student_email }}</p>
                        </div>
                        <Badge :variant="statusVariant(student.enrollment_status)">
                            {{ student.enrollment_status }}
                        </Badge>
                    </div>
                    <div class="flex flex-col gap-2">
                        <div class="flex justify-between gap-3 text-sm">
                            <span
                                >{{ student.completed_lessons }} de
                                {{ student.total_lessons }} lecciones</span
                            >
                            <span>{{ student.percentage }}%</span>
                        </div>
                        <ProgressBar :percentage="student.percentage" />
                    </div>
                    <div v-if="student.results.length > 0" class="flex flex-col gap-2">
                        <h3 class="text-sm font-semibold">Resultados de evaluaciones</h3>
                        <ul class="flex flex-col gap-2">
                            <li
                                v-for="result in student.results"
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
