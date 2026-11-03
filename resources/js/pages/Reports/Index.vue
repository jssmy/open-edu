<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import Card from '@/design-system/Card.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import FormField from '@/design-system/FormField.vue';
import Select from '@/design-system/Select.vue';

type ReportType = 'enrollments' | 'progress' | 'results';

interface ReportRow {
    course_id: number;
    course_name: string;
    student_id: number;
    student_name: string;
    student_email?: string;
    status?: string;
    enrollment_status?: string;
    enrolled_at?: string;
    completed_lessons?: number;
    total_lessons?: number;
    percentage?: number;
    assessment_id?: number;
    assessment_title?: string;
    attempt_number?: number;
    score?: number;
    total_score?: number;
    passed?: boolean;
    submitted_at?: string;
}

const props = defineProps<{
    report: ReportType;
    rows: ReportRow[];
    filters: {
        course_id?: number;
        student_id?: number;
        status?: string;
        passed?: boolean;
    };
    courses: { id: number; name: string }[];
    students: { id: number; name: string }[];
}>();

const form = useForm({
    course_id: props.filters.course_id?.toString() ?? '',
    student_id: props.filters.student_id?.toString() ?? '',
    status: props.filters.status ?? '',
    passed: props.filters.passed === undefined ? '' : props.filters.passed ? '1' : '0',
});

const title = computed(
    () =>
        ({
            enrollments: 'Reporte de inscripciones',
            progress: 'Reporte de progreso',
            results: 'Reporte de resultados',
        })[props.report],
);

const courseOptions = [
    { value: '', label: 'Todos los cursos' },
    ...props.courses.map((course) => ({ value: String(course.id), label: course.name })),
];
const studentOptions = [
    { value: '', label: 'Todos los estudiantes' },
    ...props.students.map((student) => ({ value: String(student.id), label: student.name })),
];
const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'active', label: 'Activa' },
    { value: 'suspended', label: 'Suspendida' },
    { value: 'cancelled', label: 'Cancelada' },
];
const resultOptions = [
    { value: '', label: 'Todos los resultados' },
    { value: '1', label: 'Aprobado' },
    { value: '0', label: 'No aprobado' },
];

function filterParams(): Record<string, string> {
    const params: Record<string, string> = {};

    if (form.course_id) {
        params.course_id = form.course_id;
    }
    if (form.student_id) {
        params.student_id = form.student_id;
    }
    if (form.status) {
        params.status = form.status;
    }
    if (form.passed !== '') {
        params.passed = form.passed;
    }

    return params;
}

function applyFilters() {
    router.get(`/reports/${props.report}`, filterParams(), {
        preserveState: true,
        replace: true,
    });
}

const csvHref = computed(() => {
    const params = new globalThis.URLSearchParams(filterParams());
    params.set('format', 'csv');

    return `/reports/${props.report}?${params.toString()}`;
});

function dateLabel(value: string | undefined): string {
    return value ? new Date(value).toLocaleString() : '—';
}
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <main class="flex flex-col gap-6">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-semibold">{{ title }}</h1>
            </header>

            <nav class="flex flex-wrap gap-4 text-sm" aria-label="Tipos de reportes">
                <Link href="/reports/enrollments" class="text-primary hover:underline">Inscripciones</Link>
                <Link href="/reports/progress" class="text-primary hover:underline">Progreso</Link>
                <Link href="/reports/results" class="text-primary hover:underline">Resultados</Link>
            </nav>

            <Card class="flex flex-col gap-4">
                <form class="grid gap-4 md:grid-cols-4" @submit.prevent="applyFilters">
                    <FormField id="report-course" label="Curso">
                        <Select id="report-course" v-model="form.course_id" :options="courseOptions" />
                    </FormField>
                    <FormField id="report-student" label="Estudiante">
                        <Select id="report-student" v-model="form.student_id" :options="studentOptions" />
                    </FormField>
                    <FormField v-if="report !== 'results'" id="report-status" label="Estado de inscripción">
                        <Select id="report-status" v-model="form.status" :options="statusOptions" />
                    </FormField>
                    <FormField v-else id="report-passed" label="Resultado">
                        <Select id="report-passed" v-model="form.passed" :options="resultOptions" />
                    </FormField>
                    <div class="flex items-end gap-3">
                        <Button type="submit" :loading="form.processing">Filtrar</Button>
                        <a
                            :href="csvHref"
                            class="inline-flex items-center rounded-control border border-border px-4 py-2 text-sm font-medium text-text hover:bg-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        >
                            Exportar CSV
                        </a>
                    </div>
                </form>
            </Card>

            <EmptyState
                v-if="rows.length === 0"
                title="No hay resultados para estos filtros"
                description="Prueba con otros criterios o limpia los filtros."
            />
            <Card v-else class="overflow-x-auto p-0">
                <table class="w-full border-collapse text-left text-sm">
                    <caption class="sr-only">
                        {{
                            title
                        }}
                    </caption>
                    <thead class="bg-surface text-text-muted">
                        <tr>
                            <th scope="col" class="p-3 font-medium">Curso</th>
                            <th scope="col" class="p-3 font-medium">Estudiante</th>
                            <th v-if="report === 'enrollments'" scope="col" class="p-3 font-medium">
                                Correo
                            </th>
                            <th v-if="report === 'enrollments'" scope="col" class="p-3 font-medium">
                                Estado
                            </th>
                            <th v-if="report === 'enrollments'" scope="col" class="p-3 font-medium">
                                Inscrito
                            </th>
                            <th v-if="report === 'progress'" scope="col" class="p-3 font-medium">Avance</th>
                            <th v-if="report === 'progress'" scope="col" class="p-3 font-medium">
                                Estado de inscripción
                            </th>
                            <th v-if="report === 'results'" scope="col" class="p-3 font-medium">
                                Evaluación
                            </th>
                            <th v-if="report === 'results'" scope="col" class="p-3 font-medium">Puntaje</th>
                            <th v-if="report === 'results'" scope="col" class="p-3 font-medium">Resultado</th>
                            <th v-if="report === 'results'" scope="col" class="p-3 font-medium">Enviado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="row in rows"
                            :key="`${row.course_id}-${row.student_id}-${row.assessment_id ?? row.status ?? ''}`"
                        >
                            <th scope="row" class="border-t border-border p-3 font-medium">
                                {{ row.course_name }}
                            </th>
                            <td class="border-t border-border p-3">{{ row.student_name }}</td>
                            <td v-if="report === 'enrollments'" class="border-t border-border p-3">
                                {{ row.student_email }}
                            </td>
                            <td v-if="report === 'enrollments'" class="border-t border-border p-3">
                                <Badge>{{ row.status }}</Badge>
                            </td>
                            <td v-if="report === 'enrollments'" class="border-t border-border p-3">
                                {{ dateLabel(row.enrolled_at) }}
                            </td>
                            <td v-if="report === 'progress'" class="border-t border-border p-3">
                                {{ row.completed_lessons }} / {{ row.total_lessons }} ({{ row.percentage }}%)
                            </td>
                            <td v-if="report === 'progress'" class="border-t border-border p-3">
                                <Badge>{{ row.enrollment_status }}</Badge>
                            </td>
                            <td v-if="report === 'results'" class="border-t border-border p-3">
                                {{ row.assessment_title }}
                            </td>
                            <td v-if="report === 'results'" class="border-t border-border p-3">
                                {{ row.score }} / {{ row.total_score }} · intento {{ row.attempt_number }}
                            </td>
                            <td v-if="report === 'results'" class="border-t border-border p-3">
                                <Badge :variant="row.passed ? 'success' : 'danger'">
                                    {{ row.passed ? 'Aprobado' : 'No aprobado' }}
                                </Badge>
                            </td>
                            <td v-if="report === 'results'" class="border-t border-border p-3">
                                {{ dateLabel(row.submitted_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </Card>
        </main>
    </AppLayout>
</template>
