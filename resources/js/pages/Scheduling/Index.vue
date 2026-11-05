<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import FormField from '@/design-system/FormField.vue';
import IconButton from '@/design-system/IconButton.vue';
import Input from '@/design-system/Input.vue';
import Modal from '@/design-system/Modal.vue';
import Pagination from '@/design-system/Pagination.vue';
import ScheduleCalendar from '@/design-system/ScheduleCalendar.vue';
import SearchableSelect from '@/design-system/SearchableSelect.vue';
import Select from '@/design-system/Select.vue';
import Table from '@/design-system/Table.vue';
import { requestAlertDialog } from '@/composables/useAlertDialog';
import {
    dateForIsoDay,
    type CalendarBlockMove,
    type CalendarScheduleBlock,
    type CalendarSelection,
} from '@/composables/useScheduleCalendar';
import { useListFilters } from '@/services/listFilters';

interface SessionItem {
    id?: number;
    title: string;
    session_date: string;
    day_of_week?: number;
    starts_at: string;
    ends_at: string;
    teacher_id: number;
    teacher_name?: string;
    classroom_id: number | null;
    classroom_name?: string | null;
    meeting_url: string | null;
    status: 'scheduled' | 'in_progress' | 'finished' | 'cancelled';
}

interface OfferingRow {
    id: number;
    course_id: number;
    course_name: string;
    group_code: string;
    starts_on: string;
    ends_on: string;
    max_students: number;
    enrolled_count: number;
    available_spots: number;
    modality: 'in_person' | 'virtual' | 'hybrid';
    status: 'draft' | 'published' | 'in_progress' | 'finished' | 'cancelled';
    raw_status: 'draft' | 'published' | 'in_progress' | 'finished' | 'cancelled';
    teacher_ids: number[];
    teacher_names: string[];
    teacher_name: string;
    sessions: SessionItem[];
}

interface PageMeta {
    page: number;
    last_page: number;
    total: number;
    from: number;
    to: number;
}

const props = defineProps<{
    schedules: { data: OfferingRow[]; meta: PageMeta };
    calendarBlocks: CalendarScheduleBlock[];
    calendarOfferings?: OfferingRow[];
    filters: {
        q: string;
        course: string;
        teacher: string;
        classroom: string;
        status: string;
    };
    courses: { id: number; name: string; next_group_code?: string }[];
    teachers: { id: number; name: string }[];
    classrooms: { id: number; name: string; capacity: number | null }[];
    pendingCount: number;
}>();

const { filters, goToPage, reset } = useListFilters('/scheduling', {
    q: props.filters.q ?? '',
    course: props.filters.course ?? '',
    teacher: props.filters.teacher ?? '',
    classroom: props.filters.classroom ?? '',
    status: props.filters.status ?? '',
});

function handleTeacherChange(value: string | number | null | undefined): void {
    const teacherId = value !== null && value !== undefined ? String(value) : '';
    filters.teacher = teacherId;
    goToPage(1);
}

const hasTableFilters = computed(
    () =>
        filters.q !== '' ||
        filters.course !== '' ||
        filters.classroom !== '' ||
        filters.status !== '',
);

function resetTableFilters(): void {
    filters.q = '';
    filters.course = '';
    filters.classroom = '';
    filters.status = '';
    goToPage(1);
}

const modalityLabels: Record<string, string> = {
    in_person: 'Presencial',
    virtual: 'Virtual',
    hybrid: 'Híbrida',
};

const statusLabels: Record<string, string> = {
    draft: 'Borrador',
    published: 'Publicada',
    in_progress: 'En curso',
    finished: 'Finalizada',
    cancelled: 'Cancelada',
};

const sessionStatusLabels: Record<string, string> = {
    scheduled: 'Programada',
    in_progress: 'En curso',
    finished: 'Finalizada',
    cancelled: 'Cancelada',
};

const modalityOptions = [
    { value: 'in_person', label: 'Presencial' },
    { value: 'virtual', label: 'Virtual' },
    { value: 'hybrid', label: 'Híbrida' },
];

const offeringStatusOptions = [
    { value: 'draft', label: 'Borrador' },
    { value: 'published', label: 'Publicada (en curso / finalizada automático)' },
    { value: 'cancelled', label: 'Cancelada' },
];

const sessionStatusOptions = [
    { value: 'scheduled', label: 'Programada (automático por fecha)' },
    { value: 'cancelled', label: 'Cancelada' },
];

const courseFilterOptions = computed(() => [
    { value: '', label: 'Todos los cursos' },
    ...props.courses.map((c) => ({ value: String(c.id), label: c.name })),
]);
const teacherFilterOptions = computed(() => [
    { value: '', label: 'Todos los docentes' },
    ...props.teachers.map((t) => ({ value: String(t.id), label: t.name })),
]);
const classroomFilterOptions = computed(() => [
    { value: '', label: 'Todas las aulas' },
    ...props.classrooms.map((c) => ({ value: String(c.id), label: c.name })),
]);
const statusFilterOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'draft', label: 'Borrador' },
    { value: 'published', label: 'Publicada' },
    { value: 'in_progress', label: 'En curso' },
    { value: 'finished', label: 'Finalizada' },
    { value: 'cancelled', label: 'Cancelada' },
];

const courseOptions = computed(() =>
    props.courses.map((c) => ({ value: String(c.id), label: c.name })),
);
const teacherOptions = computed(() =>
    props.teachers.map((t) => ({ value: String(t.id), label: t.name })),
);
const classroomOptions = computed(() => [
    { value: '', label: 'Seleccione un aula' },
    ...props.classrooms.map((c) => ({
        value: String(c.id),
        label: c.capacity ? `${c.name} (${c.capacity} cupos)` : c.name,
    })),
]);

const columns = [
    { key: 'course', label: 'Curso y grupo' },
    { key: 'teachers', label: 'Docente asignado' },
    { key: 'modality', label: 'Modalidad y cupos' },
    { key: 'sessions', label: 'Sesiones programadas' },
    { key: 'actions', label: 'Acciones' },
];

const modalOpen = ref(false);
const editingId = ref<number | null>(null);

const todayIso = new Date().toISOString().slice(0, 10);

function nextGroupCodeForCourse(courseId: string | number | null | undefined): string {
    const id = Number(courseId);
    const course = props.courses.find((c) => c.id === id);
    if (course?.next_group_code) {
        return course.next_group_code;
    }
    const existingForCourse = props.schedules.data.filter((row) => row.course_id === id).length;
    return `GRP-${String(existingForCourse + 1).padStart(2, '0')}`;
}

function handleCourseChange(value: string | number | null | undefined): void {
    const courseId = value ? String(value) : '';
    form.course_id = courseId;
    if (editingId.value === null) {
        form.group_code = nextGroupCodeForCourse(courseId);
    }
}

const form = useForm({
    course_id: props.courses[0] ? String(props.courses[0].id) : '',
    group_code: props.courses[0] ? nextGroupCodeForCourse(props.courses[0].id) : 'GRP-01',
    starts_on: todayIso,
    ends_on: '2026-12-31',
    max_students: '30',
    modality: 'in_person' as 'in_person' | 'virtual' | 'hybrid',
    status: 'published' as 'draft' | 'published' | 'in_progress' | 'finished' | 'cancelled',
    teacher_id: props.teachers[0] ? String(props.teachers[0].id) : '',
    sessions: [
        {
            title: 'Sesión 1',
            session_date: todayIso,
            starts_at: '08:00',
            ends_at: '10:00',
            classroom_id: props.classrooms[0] ? String(props.classrooms[0].id) : '',
            meeting_url: '',
            status: 'scheduled' as 'scheduled' | 'in_progress' | 'finished' | 'cancelled',
        },
    ],
});

const isSaving = computed(() => form.processing);

function openCreate(initialBlock?: CalendarSelection): void {
    editingId.value = null;
    form.clearErrors();
    const activeTeacher = filters.teacher !== '' ? filters.teacher : (props.teachers[0] ? String(props.teachers[0].id) : '');
    const defaultClassroom = props.classrooms[0] ? String(props.classrooms[0].id) : '';
    form.course_id = props.courses[0] ? String(props.courses[0].id) : '';
    form.group_code = nextGroupCodeForCourse(form.course_id);
    form.starts_on = todayIso;
    form.ends_on = '2026-12-31';
    form.max_students = String(props.classrooms[0]?.capacity ?? 30);
    form.modality = 'in_person';
    form.status = 'published';
    form.teacher_id = activeTeacher;
    form.sessions = [
        initialBlock
            ? {
                  title: 'Sesión 1',
                  session_date: dateForIsoDay(initialBlock.dayOfWeek),
                  starts_at: initialBlock.startsAt,
                  ends_at: initialBlock.endsAt,
                  classroom_id: defaultClassroom,
                  meeting_url: '',
                  status: 'scheduled',
              }
            : {
                  title: 'Sesión 1',
                  session_date: todayIso,
                  starts_at: '08:00',
                  ends_at: '10:00',
                  classroom_id: defaultClassroom,
                  meeting_url: '',
                  status: 'scheduled',
              },
    ];
    modalOpen.value = true;
}

function openEdit(row: OfferingRow): void {
    editingId.value = row.id;
    form.clearErrors();
    form.course_id = String(row.course_id);
    form.group_code = row.group_code;
    form.starts_on = row.starts_on;
    form.ends_on = row.ends_on;
    form.max_students = String(row.max_students);
    form.modality = row.modality;
    form.status = row.raw_status === 'draft' || row.raw_status === 'cancelled' ? row.raw_status : 'published';
    form.teacher_id = row.teacher_ids[0] ? String(row.teacher_ids[0]) : '';
    form.sessions = row.sessions.map((s) => ({
        title: s.title,
        session_date: s.session_date,
        starts_at: s.starts_at,
        ends_at: s.ends_at,
        classroom_id: s.classroom_id ? String(s.classroom_id) : '',
        meeting_url: s.meeting_url ?? '',
        status: s.status === 'cancelled' ? 'cancelled' : 'scheduled',
    }));
    modalOpen.value = true;
}

function addSession(): void {
    const defaultClassroom = props.classrooms[0] ? String(props.classrooms[0].id) : '';
    form.sessions.push({
        title: `Sesión ${form.sessions.length + 1}`,
        session_date: form.starts_on || todayIso,
        starts_at: '08:00',
        ends_at: '10:00',
        classroom_id: form.modality === 'virtual' ? '' : defaultClassroom,
        meeting_url: '',
        status: 'scheduled',
    });
}

function removeSession(index: number): void {
    if (form.sessions.length > 1) {
        form.sessions.splice(index, 1);
    }
}

function saveSchedule(): void {
    const teacherId = Number(form.teacher_id);
    const payload = {
        course_id: Number(form.course_id),
        group_code: editingId.value ? form.group_code : '',
        starts_on: form.starts_on,
        ends_on: form.ends_on,
        max_students: Number(form.max_students),
        modality: form.modality,
        status: form.status,
        teacher_id: teacherId,
        teacher_ids: teacherId ? [teacherId] : [],
        sessions: form.sessions.map((s) => ({
            title: s.title,
            session_date: s.session_date,
            starts_at: s.starts_at,
            ends_at: s.ends_at,
            teacher_id: teacherId,
            classroom_id:
                form.modality !== 'virtual' && s.classroom_id ? Number(s.classroom_id) : null,
            meeting_url: form.modality !== 'in_person' && s.meeting_url ? s.meeting_url : null,
            status: s.status,
        })),
    };

    const url = editingId.value ? `/scheduling/schedules/${editingId.value}` : '/scheduling/schedules';
    const method = editingId.value ? 'put' : 'post';

    form.transform(() => payload)[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            modalOpen.value = false;
        },
    });
}

function findOfferingById(scheduleId: number): OfferingRow | undefined {
    return (
        props.schedules.data.find((item) => item.id === scheduleId) ??
        props.calendarOfferings?.find((item) => item.id === scheduleId)
    );
}

function handleCalendarEventClick(scheduleId: number): void {
    const row = findOfferingById(scheduleId);
    if (row) {
        openEdit(row);
    }
}

function handleCalendarMove(move: CalendarBlockMove): void {
    const row = findOfferingById(move.scheduleId);
    if (!row) {
        move.revert();
        return;
    }

    const updatedSessions = row.sessions.map((session, idx) =>
        idx === move.blockIndex
            ? {
                  ...session,
                  starts_at: move.startsAt,
                  ends_at: move.endsAt,
              }
            : session,
    );

    router.put(
        `/scheduling/schedules/${row.id}`,
        {
            course_id: row.course_id,
            group_code: row.group_code,
            starts_on: row.starts_on,
            ends_on: row.ends_on,
            max_students: row.max_students,
            modality: row.modality,
            status: row.raw_status,
            teacher_ids: row.teacher_ids,
            sessions: updatedSessions.map((s) => ({ ...s })),
        },
        {
            preserveScroll: true,
            onError: () => move.revert(),
        },
    );
}

async function confirmDelete(row: OfferingRow): Promise<void> {
    if (
        await requestAlertDialog({
            variant: 'danger',
            title: '¿Eliminar esta programación académica?',
            message: `Se eliminará el grupo ${row.group_code} del curso ${row.course_name} junto con sus sesiones.`,
            confirmLabel: 'Eliminar programación',
        })
    ) {
        router.delete(`/scheduling/schedules/${row.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Programación académica" />
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold">Programación académica</h1>
                <p class="text-sm text-text-muted">
                    Gestiona grupos (CourseOffering), docentes habilitados, cupos máximos y sesiones individuales por fecha.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Link
                    href="/scheduling/pending"
                    class="inline-flex min-h-11 items-center gap-2 rounded-control border border-border bg-panel px-3 py-2 text-sm font-medium text-text hover:bg-surface"
                >
                    Por programar
                    <Badge v-if="pendingCount > 0" variant="warning">{{ pendingCount }}</Badge>
                </Link>
                <Link
                    href="/scheduling/classrooms"
                    class="inline-flex min-h-11 items-center rounded-control border border-border bg-panel px-3 py-2 text-sm font-medium text-text hover:bg-surface"
                >
                    Aulas
                </Link>
                <Button @click="openCreate()">Nueva programación</Button>
            </div>
        </div>

        <!-- Selector principal de Docente para la gestión horaria -->
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-panel border border-border bg-panel p-4 shadow-sm">
            <div class="flex flex-col gap-1">
                <span class="text-sm font-medium text-text">Gestión por docente</span>
                <span class="text-xs text-text-muted">
                    Selecciona un docente para ver y administrar su horario semanal y sus ofertas correspondientes.
                </span>
            </div>
            <div class="w-full sm:w-80">
                <SearchableSelect
                    id="schedule-teacher-select"
                    :model-value="filters.teacher"
                    :options="teacherFilterOptions"
                    placeholder="Buscar o seleccionar docente..."
                    @update:model-value="handleTeacherChange"
                />
            </div>
        </div>

        <div class="mb-8">
            <ScheduleCalendar
                :blocks="calendarBlocks"
                :editable="true"
                @select="openCreate"
                @move="handleCalendarMove"
                @event-click="handleCalendarEventClick"
            />
        </div>

        <!-- Filtros contextuales para la tabla inferior de ofertas -->
        <div class="mb-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-base font-semibold text-text">Listado de ofertas y grupos programados</h2>
                <span v-if="filters.teacher" class="text-xs text-brand">
                    Mostrando resultados filtrados para el docente seleccionado
                </span>
            </div>
            <form class="flex flex-wrap items-end gap-3 rounded-panel border border-border bg-panel p-3" role="search" @submit.prevent="goToPage(1)">
                <div class="min-w-48 flex-1">
                    <label for="filter-q" class="mb-1 block text-xs text-text-muted">Buscar</label>
                    <Input id="filter-q" v-model="filters.q" placeholder="Curso, grupo o docente" />
                </div>
                <div class="w-48">
                    <label for="filter-course" class="mb-1 block text-xs text-text-muted">Curso</label>
                    <Select id="filter-course" v-model="filters.course" :options="courseFilterOptions" />
                </div>
                <div class="w-48">
                    <label for="filter-classroom" class="mb-1 block text-xs text-text-muted">Aula</label>
                    <Select
                        id="filter-classroom"
                        v-model="filters.classroom"
                        :options="classroomFilterOptions"
                    />
                </div>
                <div class="w-44">
                    <label for="filter-status" class="mb-1 block text-xs text-text-muted">Estado</label>
                    <Select id="filter-status" v-model="filters.status" :options="statusFilterOptions" />
                </div>
                <IconButton icon="search" label="Filtrar tabla" type="submit" variant="primary" />
                <IconButton v-if="hasTableFilters" icon="clear" label="Limpiar filtros" @click="resetTableFilters" />
            </form>
        </div>

        <Table :columns="columns" :rows="schedules.data as unknown as Record<string, unknown>[]">
            <template #cell-course="{ row }">
                <p class="font-semibold">{{ row.course_name }}</p>
                <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-text-muted">
                    <span class="font-medium text-text">{{ row.group_code }}</span>
                    <span>· {{ row.starts_on }} al {{ row.ends_on }}</span>
                    <Badge
                        :variant="
                            row.status === 'in_progress' || row.status === 'published'
                                ? 'success'
                                : row.status === 'cancelled'
                                  ? 'danger'
                                  : 'neutral'
                        "
                    >
                        {{ statusLabels[String(row.status)] ?? row.status }}
                    </Badge>
                </div>
            </template>
            <template #cell-teachers="{ row }">
                <span class="text-sm">{{ row.teacher_name }}</span>
            </template>
            <template #cell-modality="{ row }">
                <div class="flex flex-col gap-1">
                    <Badge :variant="row.modality === 'virtual' ? 'neutral' : 'success'">
                        {{ modalityLabels[String(row.modality)] ?? row.modality }}
                    </Badge>
                    <span class="text-xs text-text-muted">
                        Vacantes: <strong class="text-text">{{ row.available_spots }}</strong> / {{ row.max_students }}
                    </span>
                </div>
            </template>
            <template #cell-sessions="{ row }">
                <ul class="space-y-1 text-xs">
                    <li
                        v-for="(session, idx) in row.sessions as SessionItem[]"
                        :key="idx"
                        class="flex flex-wrap items-center gap-1"
                    >
                        <span class="font-medium">{{ session.session_date }}</span>
                        <span>({{ session.starts_at }} – {{ session.ends_at }})</span>
                        <span v-if="session.classroom_name" class="text-text-muted">
                            [{{ session.classroom_name }}]
                        </span>
                        <Badge v-if="session.status === 'cancelled'" variant="danger">
                            {{ sessionStatusLabels[session.status] }}
                        </Badge>
                    </li>
                </ul>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex items-center gap-2">
                    <IconButton
                        icon="edit"
                        :label="`Editar programación de ${row.course_name}`"
                        variant="primary"
                        @click="openEdit(row as unknown as OfferingRow)"
                    />
                    <IconButton
                        icon="delete"
                        :label="`Eliminar programación de ${row.course_name}`"
                        variant="danger"
                        @click="confirmDelete(row as unknown as OfferingRow)"
                    />
                </div>
            </template>
            <template #empty>
                <EmptyState
                    :title="hasTableFilters || Boolean(filters.teacher) ? 'Sin resultados para los filtros' : 'Aún no hay programaciones académicas'"
                    :description="
                        hasTableFilters || Boolean(filters.teacher)
                            ? 'Prueba ajustando el curso, docente, aula o estado filtrado.'
                            : 'Crea una programación académica indicando el grupo, fechas, cupos y sesiones.'
                    "
                />
            </template>
        </Table>

        <Pagination
            class="mt-4"
            :page="schedules.meta.page"
            :last-page="schedules.meta.last_page"
            :total="schedules.meta.total"
            :from="schedules.meta.from"
            :to="schedules.meta.to"
            @change="goToPage"
        />

        <Modal
            :open="modalOpen"
            :title="editingId ? 'Editar programación académica' : 'Nueva programación académica'"
            size="4xl"
            @close="modalOpen = false"
        >
            <form class="flex flex-col gap-5" @submit.prevent="saveSchedule">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField id="schedule-course" label="Curso" :error="form.errors.course_id">
                        <SearchableSelect
                            id="schedule-course"
                            v-model="form.course_id"
                            :options="courseOptions"
                            :error="form.errors.course_id"
                            placeholder="Seleccione un curso..."
                            search-placeholder="Buscar curso por nombre..."
                            empty-message="No hay cursos disponibles."
                            no-results-message="No se encontraron cursos."
                            @update:model-value="handleCourseChange"
                        />
                    </FormField>

                    <FormField id="schedule-group" label="Código del grupo (autogenerado)" :error="form.errors.group_code">
                        <Input id="schedule-group" v-model="form.group_code" :disabled="true" />
                    </FormField>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <FormField id="schedule-starts-on" label="Fecha de inicio" :error="form.errors.starts_on">
                        <Input id="schedule-starts-on" v-model="form.starts_on" type="date" />
                    </FormField>
                    <FormField id="schedule-ends-on" label="Fecha de fin" :error="form.errors.ends_on">
                        <Input id="schedule-ends-on" v-model="form.ends_on" type="date" />
                    </FormField>
                    <FormField id="schedule-max-students" label="Cupos máximos" :error="form.errors.max_students">
                        <Input id="schedule-max-students" v-model="form.max_students" type="number" min="1" />
                    </FormField>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <FormField id="schedule-modality" label="Modalidad" :error="form.errors.modality">
                        <Select id="schedule-modality" v-model="form.modality" :options="modalityOptions" />
                    </FormField>
                    <FormField id="schedule-status" label="Estado" :error="form.errors.status">
                        <Select id="schedule-status" v-model="form.status" :options="offeringStatusOptions" />
                    </FormField>
                </div>

                <FormField
                    id="schedule-teacher"
                    label="Docente asignado"
                    :error="(form.errors as Record<string, string>).teacher_id || (form.errors as Record<string, string>).teacher_ids"
                >
                    <SearchableSelect
                        id="schedule-teacher"
                        v-model="form.teacher_id"
                        :options="teacherOptions"
                        :error="(form.errors as Record<string, string>).teacher_id || (form.errors as Record<string, string>).teacher_ids"
                        placeholder="Seleccione un docente..."
                        search-placeholder="Buscar docente por nombre..."
                        empty-message="No hay docentes disponibles."
                        no-results-message="No se encontraron docentes."
                    />
                </FormField>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-text">Sesiones individuales programadas</p>
                        <Button type="button" variant="secondary" @click="addSession">
                            Añadir sesión
                        </Button>
                    </div>
                    <p v-if="(form.errors as Record<string, string>).sessions" class="text-xs text-danger">
                        {{ (form.errors as Record<string, string>).sessions }}
                    </p>
                    <p v-if="(form.errors as Record<string, string>).classroom_id" class="text-xs text-danger">
                        {{ (form.errors as Record<string, string>).classroom_id }}
                    </p>
                    <div
                        v-for="(session, index) in form.sessions"
                        :key="index"
                        class="space-y-3 rounded-card border border-border bg-surface/40 p-4"
                    >
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
                            <div>
                                <label :for="`session-date-${index}`" class="mb-1 block text-xs font-medium text-text-muted">
                                    Fecha
                                </label>
                                <Input :id="`session-date-${index}`" v-model="session.session_date" type="date" />
                            </div>
                            <div>
                                <label :for="`session-start-${index}`" class="mb-1 block text-xs font-medium text-text-muted">
                                    Hora inicio
                                </label>
                                <Input :id="`session-start-${index}`" v-model="session.starts_at" type="time" />
                            </div>
                            <div>
                                <label :for="`session-end-${index}`" class="mb-1 block text-xs font-medium text-text-muted">
                                    Hora fin
                                </label>
                                <Input :id="`session-end-${index}`" v-model="session.ends_at" type="time" />
                            </div>
                            <div>
                                <label :for="`session-status-${index}`" class="mb-1 block text-xs font-medium text-text-muted">
                                    Estado
                                </label>
                                <Select
                                    :id="`session-status-${index}`"
                                    v-model="session.status"
                                    :options="sessionStatusOptions"
                                />
                            </div>
                            <div class="flex justify-end">
                                <IconButton
                                    v-if="form.sessions.length > 1"
                                    icon="delete"
                                    label="Quitar sesión"
                                    variant="danger"
                                    @click="removeSession(index)"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div v-if="form.modality !== 'virtual'">
                                <label :for="`session-room-${index}`" class="mb-1 block text-xs text-text-muted">
                                    Aula física
                                </label>
                                <Select
                                    :id="`session-room-${index}`"
                                    v-model="session.classroom_id"
                                    :options="classroomOptions"
                                />
                            </div>
                            <div v-if="form.modality !== 'in_person'">
                                <label :for="`session-url-${index}`" class="mb-1 block text-xs text-text-muted">
                                    Enlace de videoconferencia
                                </label>
                                <Input
                                    :id="`session-url-${index}`"
                                    v-model="session.meeting_url"
                                    placeholder="https://meet.openedu.test/..."
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <Button type="button" variant="secondary" @click="modalOpen = false">
                        Cancelar
                    </Button>
                    <Button type="submit" :loading="isSaving">Guardar programación</Button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
