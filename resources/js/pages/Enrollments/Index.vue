<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import Card from '@/design-system/Card.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import FormField from '@/design-system/FormField.vue';
import Input from '@/design-system/Input.vue';
import Modal from '@/design-system/Modal.vue';
import ScheduleCalendar from '@/design-system/ScheduleCalendar.vue';
import SearchableSelect from '@/design-system/SearchableSelect.vue';
import type { CalendarScheduleBlock } from '@/composables/useScheduleCalendar';
import { requestAlertDialog } from '@/composables/useAlertDialog';

interface StudentOption {
    id: number;
    name: string;
    email: string;
    identifier: string | null;
}

interface SessionItem {
    id: number;
    title: string;
    session_date: string;
    day_of_week: number;
    starts_at: string;
    ends_at: string;
    teacher_id: number;
    teacher_name: string;
    classroom_id: number | null;
    classroom_name: string | null;
    meeting_url: string | null;
    status: 'scheduled' | 'in_progress' | 'finished' | 'cancelled';
}

interface OfferingItem {
    id: number;
    course_id: number;
    group_code: string;
    starts_on: string | null;
    ends_on: string | null;
    modality: 'in_person' | 'virtual' | 'hybrid';
    status: 'draft' | 'published' | 'in_progress' | 'finished' | 'cancelled';
    max_students: number;
    enrolled_count: number;
    available_spots: number;
    teacher_names: string[];
    teacher_name: string;
    sessions: SessionItem[];
}

interface CourseItem {
    id: number;
    name: string;
    code: string | null;
    description: string | null;
    offerings_count: number;
    total_available_spots: number;
    is_enrolled: boolean;
    enrolled_group_code: string | null;
    enrollment_id: number | null;
    offerings: OfferingItem[];
}

interface StudentEnrollmentItem {
    id: number;
    course_id: number;
    course_name: string;
    course_offering_id: number | null;
    group_code: string;
    starts_on: string | null;
    ends_on: string | null;
    modality: 'in_person' | 'virtual' | 'hybrid';
    teacher_name: string;
    enrolled_at: string;
}

const props = defineProps<{
    students: StudentOption[];
    selectedStudentId: number | null;
    courses: CourseItem[];
    studentEnrollments: StudentEnrollmentItem[];
    calendarBlocks: CalendarScheduleBlock[];
    filters: {
        q: string;
        student_id: number | null;
    };
}>();

const searchQuery = ref(props.filters.q ?? '');
const selectedCourseId = ref<number | null>(null);

const selectedCourse = computed(
    () => props.courses.find((course) => course.id === selectedCourseId.value) ?? null,
);

const studentOptions = computed(() =>
    props.students.map((student) => ({
        value: student.id,
        label: student.identifier
            ? `${student.name} · ${student.identifier} (${student.email})`
            : `${student.name} (${student.email})`,
    })),
);

const enrollForm = useForm({
    student_id: props.selectedStudentId ?? ('' as number | ''),
    course_offering_id: '' as number | '',
});

const modalityLabels: Record<string, string> = {
    in_person: 'Presencial',
    virtual: 'Virtual',
    hybrid: 'Híbrida',
};

const statusLabels: Record<string, string> = {
    published: 'Publicado',
    in_progress: 'En curso',
    finished: 'Finalizado',
    cancelled: 'Cancelado',
    scheduled: 'Programada',
};

const dayNames: Record<number, string> = {
    1: 'Lun',
    2: 'Mar',
    3: 'Mié',
    4: 'Jue',
    5: 'Vie',
    6: 'Sáb',
    7: 'Dom',
};

function selectStudent(value: string | number | null | undefined): void {
    const nextId = value ? Number(value) : null;
    enrollForm.student_id = nextId ?? '';
    router.get(
        '/enrollments',
        {
            student_id: nextId || undefined,
            q: searchQuery.value.trim() || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function applySearch(): void {
    router.get(
        '/enrollments',
        {
            student_id: props.selectedStudentId || undefined,
            q: searchQuery.value.trim() || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function openCourseModal(course: CourseItem): void {
    enrollForm.clearErrors();
    selectedCourseId.value = course.id;
}

function closeCourseModal(): void {
    selectedCourseId.value = null;
    enrollForm.clearErrors();
}

function enrollInGroup(offeringId: number): void {
    if (!props.selectedStudentId) {
        return;
    }

    enrollForm.student_id = props.selectedStudentId;
    enrollForm.course_offering_id = offeringId;
    enrollForm.post('/enrollments', {
        preserveScroll: true,
        onSuccess: () => {
            closeCourseModal();
        },
    });
}

async function confirmUnenroll(enrollment: StudentEnrollmentItem): Promise<void> {
    const confirmed = await requestAlertDialog({
        variant: 'danger',
        title: 'Desmatricular estudiante',
        message: `¿Confirmas desmatricular al estudiante del curso «${enrollment.course_name}» (${enrollment.group_code})? Se liberará inmediatamente su cupo y se actualizará su horario en tiempo real.`,
        confirmLabel: 'Desmatricular',
        cancelLabel: 'Cancelar',
    });

    if (!confirmed) {
        return;
    }

    router.delete(`/enrollments/${enrollment.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Matrículas de estudiantes" />

        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-text">Matrícula de estudiantes en clases</h1>
                <p class="mt-1 text-sm text-text-muted">
                    Selecciona un estudiante, consulta la programación académica de cada curso y gestiona su horario en tiempo real.
                </p>
            </div>
        </div>

        <Card class="mb-6">
            <div class="grid gap-4 md:grid-cols-2">
                <FormField
                    id="student-selector"
                    label="Estudiante activo"
                    :error="enrollForm.errors.student_id"
                >
                    <SearchableSelect
                        id="student-selector"
                        :model-value="selectedStudentId"
                        :options="studentOptions"
                        placeholder="Selecciona un estudiante activo..."
                        search-placeholder="Buscar estudiante por nombre, documento o correo..."
                        empty-message="No hay estudiantes activos registrados."
                        @update:model-value="selectStudent"
                    />
                </FormField>

                <form class="flex items-end gap-2" @submit.prevent="applySearch">
                    <FormField id="course-search" label="Buscar cursos publicados" class="flex-1">
                        <Input
                            id="course-search"
                            v-model="searchQuery"
                            placeholder="Filtrar por nombre o código del curso..."
                        />
                    </FormField>
                    <Button type="submit" variant="secondary">Buscar</Button>
                </form>
            </div>
        </Card>

        <Alert
            v-if="enrollForm.errors.course_offering_id || enrollForm.errors.student_id"
            variant="danger"
            class="mb-6"
        >
            {{ enrollForm.errors.course_offering_id ?? enrollForm.errors.student_id }}
        </Alert>

        <EmptyState
            v-if="students.length === 0"
            title="No hay estudiantes activos disponibles"
            description="Registra o activa un estudiante en el módulo de Estudiantes para gestionar su matrícula."
        />

        <div v-else class="grid gap-6 xl:grid-cols-12">
            <!-- Catálogo de cursos disponibles -->
            <section class="space-y-4 xl:col-span-5" aria-labelledby="cursos-disponibles-heading">
                <div class="flex items-center justify-between">
                    <h2 id="cursos-disponibles-heading" class="text-base font-semibold text-text">
                        Cursos publicados disponibles ({{ courses.length }})
                    </h2>
                </div>

                <EmptyState
                    v-if="courses.length === 0"
                    title="No se encontraron cursos publicados"
                    description="Ajusta el filtro de búsqueda o publica cursos en el catálogo académico."
                />

                <ul v-else class="space-y-3">
                    <li
                        v-for="course in courses"
                        :key="course.id"
                        class="rounded-card border border-border bg-panel p-4 transition hover:border-primary/40"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-semibold text-text">{{ course.name }}</h3>
                                    <Badge v-if="course.code" variant="neutral">{{ course.code }}</Badge>
                                    <Badge v-if="course.is_enrolled" variant="success">
                                        Matriculado · {{ course.enrolled_group_code ?? 'Activo' }}
                                    </Badge>
                                </div>
                                <p v-if="course.description" class="mt-1 line-clamp-2 text-xs text-text-muted">
                                    {{ course.description }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-3 text-xs text-text-muted">
                            <div class="flex flex-wrap items-center gap-2">
                                <span>
                                    <strong class="text-text">{{ course.offerings_count }}</strong>
                                    {{ course.offerings_count === 1 ? 'programación abierta' : 'programaciones abiertas' }}
                                </span>
                                <span>·</span>
                                <Badge :variant="course.total_available_spots > 0 ? 'success' : 'warning'">
                                    {{ course.total_available_spots }} vacantes disponibles
                                </Badge>
                            </div>
                            <Button
                                size="sm"
                                :variant="course.is_enrolled ? 'secondary' : 'primary'"
                                :data-testid="`open-course-modal-${course.id}`"
                                @click="openCourseModal(course)"
                            >
                                Ver programación y cupos
                            </Button>
                        </div>
                    </li>
                </ul>
            </section>

            <!-- Panel de matrículas activas y horario del estudiante en tiempo real -->
            <section class="space-y-6 xl:col-span-7" aria-labelledby="horario-estudiante-heading">
                <Card>
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h2 id="horario-estudiante-heading" class="text-base font-semibold text-text">
                                Cursos matriculados del estudiante ({{ studentEnrollments.length }})
                            </h2>
                            <p class="text-xs text-text-muted">
                                Puedes desmatricular un curso para liberar su cupo inmediatamente.
                            </p>
                        </div>
                    </div>

                    <EmptyState
                        v-if="studentEnrollments.length === 0"
                        title="El estudiante aún no tiene cursos matriculados"
                        description="Haz clic en un curso disponible para ver sus grupos, horarios y matricularlo."
                    />

                    <ul v-else class="divide-y divide-border">
                        <li
                            v-for="enrollment in studentEnrollments"
                            :key="enrollment.id"
                            class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-text">{{ enrollment.course_name }}</span>
                                    <Badge variant="neutral">{{ enrollment.group_code }}</Badge>
                                    <Badge variant="neutral">
                                        {{ modalityLabels[enrollment.modality] ?? enrollment.modality }}
                                    </Badge>
                                </div>
                                <p class="mt-0.5 text-xs text-text-muted">
                                    <span v-if="enrollment.teacher_name">Docente: {{ enrollment.teacher_name }} · </span>
                                    <span v-if="enrollment.starts_on && enrollment.ends_on">
                                        {{ enrollment.starts_on }} al {{ enrollment.ends_on }}
                                    </span>
                                </p>
                            </div>
                            <Button
                                variant="danger"
                                size="sm"
                                :data-testid="`unenroll-btn-${enrollment.id}`"
                                @click="confirmUnenroll(enrollment)"
                            >
                                Desmatricular
                            </Button>
                        </li>
                    </ul>
                </Card>

                <ScheduleCalendar
                    :blocks="calendarBlocks"
                    :editable="false"
                    title="Horario del estudiante en tiempo real"
                />
            </section>
        </div>

        <!-- Modal de Programación Académica del Curso -->
        <Modal
            :open="selectedCourse !== null"
            :title="selectedCourse ? `Programación académica · ${selectedCourse.name}` : 'Programación académica'"
            size="3xl"
            @close="closeCourseModal"
        >
            <div v-if="selectedCourse" class="space-y-4">
                <Alert
                    v-if="enrollForm.errors.course_offering_id || enrollForm.errors.student_id"
                    variant="danger"
                >
                    {{ enrollForm.errors.course_offering_id ?? enrollForm.errors.student_id }}
                </Alert>

                <Alert v-if="selectedCourse.is_enrolled" variant="info">
                    El estudiante ya cuenta con una matrícula activa en este curso ({{ selectedCourse.enrolled_group_code ?? 'Grupo activo' }}).
                </Alert>

                <EmptyState
                    v-if="selectedCourse.offerings.length === 0"
                    title="Sin programación académica disponible"
                    description="Este curso aún no tiene grupos publicados o en curso para matrícula."
                />

                <div v-else class="space-y-4">
                    <div
                        v-for="offering in selectedCourse.offerings"
                        :key="offering.id"
                        class="rounded-card border border-border bg-surface/50 p-4"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-semibold text-text">{{ offering.group_code }}</h3>
                                    <Badge variant="neutral">
                                        {{ modalityLabels[offering.modality] ?? offering.modality }}
                                    </Badge>
                                    <Badge :variant="offering.status === 'in_progress' ? 'success' : 'neutral'">
                                        {{ statusLabels[offering.status] ?? offering.status }}
                                    </Badge>
                                </div>
                                <p class="mt-1 text-xs text-text-muted">
                                    <strong>Docente:</strong> {{ offering.teacher_name || 'Por asignar' }}
                                </p>
                                <p class="text-xs text-text-muted">
                                    <strong>Vigencia:</strong> {{ offering.starts_on }} al {{ offering.ends_on }}
                                </p>
                            </div>
                            <div class="text-right">
                                <Badge :variant="offering.available_spots > 0 ? 'success' : 'warning'">
                                    {{ offering.available_spots }} / {{ offering.max_students }} cupos disponibles
                                </Badge>
                            </div>
                        </div>

                        <div class="mt-3 border-t border-border/60 pt-3">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-text-muted">
                                Sesiones programadas ({{ offering.sessions.length }})
                            </p>
                            <p v-if="offering.sessions.length === 0" class="text-xs text-text-muted">
                                Sin sesiones individuales registradas.
                            </p>
                            <ul v-else class="max-h-44 space-y-1.5 overflow-y-auto text-xs">
                                <li
                                    v-for="session in offering.sessions"
                                    :key="session.id"
                                    class="flex flex-wrap items-center justify-between gap-2 rounded-control border border-border/60 bg-panel px-2.5 py-1.5"
                                >
                                    <div>
                                        <span class="font-medium text-text">
                                            {{ dayNames[session.day_of_week] ?? '' }} {{ session.session_date }}
                                        </span>
                                        <span class="text-text-muted">
                                            ({{ session.starts_at }}–{{ session.ends_at }})
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-text-muted">
                                        <span v-if="session.teacher_name">{{ session.teacher_name }}</span>
                                        <span v-if="session.classroom_name">· {{ session.classroom_name }}</span>
                                        <Badge
                                            v-if="session.status === 'cancelled'"
                                            variant="warning"
                                        >
                                            Cancelada
                                        </Badge>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div class="mt-3 flex justify-end">
                            <Button
                                size="sm"
                                :loading="enrollForm.processing && enrollForm.course_offering_id === offering.id"
                                :disabled="
                                    selectedCourse.is_enrolled ||
                                    offering.available_spots <= 0 ||
                                    !selectedStudentId ||
                                    enrollForm.processing
                                "
                                :data-testid="`enroll-offering-${offering.id}`"
                                @click="enrollInGroup(offering.id)"
                            >
                                {{
                                    selectedCourse.is_enrolled
                                        ? 'Ya matriculado'
                                        : offering.available_spots <= 0
                                          ? 'Sin cupos disponibles'
                                          : 'Matricular en este grupo'
                                }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <template #actions>
                <div class="flex justify-end">
                    <Button variant="secondary" @click="closeCourseModal">Cancelar</Button>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>
