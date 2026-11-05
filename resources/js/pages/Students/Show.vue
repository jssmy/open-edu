<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import Card from '@/design-system/Card.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import FormField from '@/design-system/FormField.vue';
import Input from '@/design-system/Input.vue';
import { submit } from '@/services/http';

interface Student {
    id: number;
    name: string;
    email: string;
    identifier: string | null;
    phone: string | null;
    is_active: boolean;
}

interface AssessmentAttempt {
    id: number;
    attempt_number: number;
    score: number;
    passed: boolean;
    status: string;
    submitted_at: string | null;
}

interface CourseAssessment {
    id: number;
    title: string;
    total_score: number;
    passing_score: number;
    latest_attempt: AssessmentAttempt | null;
}

interface CourseProgress {
    completed_lessons: number;
    total_lessons: number;
    percentage: number;
}

interface Course {
    id: number;
    enrollment_id?: number;
    name: string;
    status: string;
    group_code?: string | null;
    enrolled_at: string;
    progress?: CourseProgress;
    assessments?: CourseAssessment[];
}

const props = defineProps<{
    student: Student;
    courses: Course[];
    canManageStudents: boolean;
    canManageEnrollments?: boolean;
}>();
const form = useForm({
    name: props.student.name,
    email: props.student.email,
    identifier: props.student.identifier ?? '',
    phone: props.student.phone ?? '',
});
</script>

<template>
    <Head :title="student.name" />
    <AppLayout>
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold">{{ student.name }}</h1>
                <Badge :variant="student.is_active ? 'success' : 'neutral'">
                    {{ student.is_active ? 'Activo' : 'Inactivo' }}
                </Badge>
            </div>
            <Button
                v-if="canManageStudents"
                variant="secondary"
                @click="
                    submit('patch', `/students/${student.id}/status`, {
                        is_active: !student.is_active,
                    })
                "
            >
                {{ student.is_active ? 'Desactivar' : 'Activar' }}
            </Button>
        </div>

        <Card v-if="canManageStudents" class="mb-6 max-w-2xl">
            <form class="grid gap-4 md:grid-cols-2" @submit.prevent="form.put(`/students/${student.id}`)">
                <FormField id="name" label="Nombre" :error="form.errors.name">
                    <Input id="name" v-model="form.name" :error="form.errors.name" />
                </FormField>
                <FormField id="email" label="Correo" :error="form.errors.email">
                    <Input id="email" v-model="form.email" type="email" :error="form.errors.email" />
                </FormField>
                <FormField id="identifier" label="Documento / identificador" :error="form.errors.identifier">
                    <Input id="identifier" v-model="form.identifier" :error="form.errors.identifier" />
                </FormField>
                <FormField id="phone" label="Teléfono" :error="form.errors.phone">
                    <Input id="phone" v-model="form.phone" :error="form.errors.phone" />
                </FormField>
                <Button type="submit" :loading="form.processing" class="md:col-span-2"
                    >Guardar cambios</Button
                >
            </form>
        </Card>

        <section>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-semibold">Cursos</h2>
                <Link
                    v-if="canManageEnrollments"
                    :href="`/enrollments?student_id=${student.id}`"
                    class="text-sm font-medium text-primary hover:underline"
                >
                    Gestionar matrícula y horario →
                </Link>
            </div>
            <EmptyState v-if="courses.length === 0" title="Sin cursos inscritos" />
            <ul v-else class="flex flex-col gap-4">
                <li
                    v-for="course in courses"
                    :key="course.id"
                    class="rounded-card border border-border bg-panel p-4 flex flex-col gap-4"
                >
                    <!-- Cabecera del Curso -->
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border pb-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <Link :href="`/courses/${course.id}`" class="text-base font-semibold text-primary hover:underline">
                                {{ course.name }}
                            </Link>
                            <Badge v-if="course.group_code" variant="neutral">Grupo {{ course.group_code }}</Badge>
                            <Badge :variant="course.status === 'active' ? 'success' : 'neutral'">{{ course.status }}</Badge>
                        </div>
                        <span class="text-xs text-text-muted">
                            Matriculado el {{ new Date(course.enrolled_at).toLocaleDateString() }}
                        </span>
                    </div>

                    <!-- Progreso del Curso -->
                    <div v-if="course.progress" class="rounded-card bg-surface-muted/50 p-3 border border-border/50">
                        <div class="mb-1.5 flex items-center justify-between text-xs font-medium">
                            <span class="text-text">Avance del contenido</span>
                            <span class="text-text-muted">
                                {{ course.progress.completed_lessons }} de {{ course.progress.total_lessons }} lecciones ({{ course.progress.percentage }}%)
                            </span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-border">
                            <div
                                class="h-full bg-primary transition-all duration-300"
                                :style="{ width: `${course.progress.percentage}%` }"
                            />
                        </div>
                    </div>

                    <!-- Evaluaciones del Curso -->
                    <div class="flex flex-col gap-2">
                        <h3 class="text-xs font-semibold uppercase tracking-wider text-text-muted">
                            Evaluaciones y Calificaciones
                        </h3>
                        <div v-if="!course.assessments || course.assessments.length === 0" class="text-xs text-text-muted italic py-1">
                            Este curso no cuenta con evaluaciones publicadas.
                        </div>
                        <div v-else class="grid gap-2 sm:grid-cols-2">
                            <div
                                v-for="assessment in course.assessments"
                                :key="assessment.id"
                                class="flex items-center justify-between gap-3 rounded-card border border-border/70 bg-panel p-2.5 text-xs"
                            >
                                <div class="flex flex-col gap-0.5 min-w-0">
                                    <span class="font-medium text-text truncate" :title="assessment.title">
                                        {{ assessment.title }}
                                    </span>
                                    <span class="text-[11px] text-text-muted">
                                        Puntaje max: {{ assessment.total_score }} · Aprueba con: {{ assessment.passing_score }}
                                    </span>
                                </div>

                                <div class="flex flex-col items-end gap-1 flex-shrink-0">
                                    <template v-if="assessment.latest_attempt">
                                        <Badge :variant="assessment.latest_attempt.passed ? 'success' : 'danger'">
                                            {{ assessment.latest_attempt.score }} / {{ assessment.total_score }}
                                            ({{ assessment.latest_attempt.passed ? 'Aprobado' : 'Desaprobado' }})
                                        </Badge>
                                        <span class="text-[10px] text-text-muted">
                                            Intento {{ assessment.latest_attempt.attempt_number }}
                                        </span>
                                    </template>
                                    <template v-else>
                                        <Badge variant="neutral">Sin rendir</Badge>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </section>
    </AppLayout>
</template>
