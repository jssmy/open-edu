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
import type {
    CalendarBlockMove,
    CalendarScheduleBlock,
    CalendarSelection,
} from '@/composables/useScheduleCalendar';
import { useListFilters } from '@/services/listFilters';

interface ScheduleBlockItem {
    id?: number;
    day_of_week: number;
    starts_at: string;
    ends_at: string;
}

interface ScheduleRow {
    id: number;
    course_id: number;
    course_name: string;
    teacher_id: number;
    teacher_name: string;
    academic_period_id: number;
    period_name: string;
    period_has_ended: boolean;
    modality: 'in_person' | 'virtual';
    classroom_id: number | null;
    classroom_name: string | null;
    blocks: ScheduleBlockItem[];
}

interface PageMeta {
    page: number;
    last_page: number;
    total: number;
    from: number;
    to: number;
}

const props = defineProps<{
    schedules: { data: ScheduleRow[]; meta: PageMeta };
    calendarBlocks: CalendarScheduleBlock[];
    filters: {
        q: string;
        course: string;
        teacher: string;
        classroom: string;
        period: string;
    };
    courses: { id: number; name: string }[];
    teachers: { id: number; name: string }[];
    classrooms: { id: number; name: string; capacity: number | null }[];
    periods: { id: number; name: string; starts_on: string; ends_on: string; has_ended: boolean }[];
    pendingCount: number;
}>();

const { filters, goToPage, reset } = useListFilters('/scheduling', {
    q: props.filters.q ?? '',
    course: props.filters.course ?? '',
    teacher: props.filters.teacher ?? '',
    classroom: props.filters.classroom ?? '',
    period: props.filters.period ?? '',
});

const hasFilters = computed(
    () =>
        filters.q !== '' ||
        filters.course !== '' ||
        filters.teacher !== '' ||
        filters.classroom !== '',
);

const dayNames: Record<number, string> = {
    1: 'Lunes',
    2: 'Martes',
    3: 'Miércoles',
    4: 'Jueves',
    5: 'Viernes',
    6: 'Sábado',
    7: 'Domingo',
};

const dayOptions = Object.entries(dayNames).map(([value, label]) => ({ value, label }));
const modalityOptions = [
    { value: 'in_person', label: 'Presencial' },
    { value: 'virtual', label: 'Virtual' },
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
const periodFilterOptions = computed(() => [
    { value: '', label: 'Todos los periodos' },
    ...props.periods.map((p) => ({ value: String(p.id), label: p.name })),
]);

const activePeriodOptions = computed(() =>
    props.periods.filter((p) => !p.has_ended).map((p) => ({ value: String(p.id), label: p.name })),
);
const courseOptions = computed(() =>
    props.courses.map((c) => ({ value: String(c.id), label: c.name })),
);
const teacherOptions = computed(() =>
    props.teachers.map((t) => ({ value: String(t.id), label: t.name })),
);
const classroomOptions = computed(() => [
    { value: '', label: 'Seleccione un aula' },
    ...props.classrooms.map((c) => ({ value: String(c.id), label: c.name })),
]);

const columns = [
    { key: 'course', label: 'Curso y periodo' },
    { key: 'teacher', label: 'Docente' },
    { key: 'location', label: 'Modalidad / Aula' },
    { key: 'blocks', label: 'Horario semanal' },
    { key: 'actions', label: 'Acciones' },
];

const modalOpen = ref(false);
const editingId = ref<number | null>(null);

const form = useForm<{
    course_id: string;
    teacher_id: string;
    academic_period_id: string;
    modality: 'in_person' | 'virtual';
    classroom_id: string;
    blocks: { day_of_week: string; starts_at: string; ends_at: string }[];
}>({
    course_id: props.courses[0] ? String(props.courses[0].id) : '',
    teacher_id: props.teachers[0] ? String(props.teachers[0].id) : '',
    academic_period_id: activePeriodOptions.value[0]?.value ?? '',
    modality: 'in_person',
    classroom_id: props.classrooms[0] ? String(props.classrooms[0].id) : '',
    blocks: [{ day_of_week: '1', starts_at: '08:00', ends_at: '10:00' }],
});

function openCreate(initialBlock?: CalendarSelection): void {
    editingId.value = null;
    form.clearErrors();
    form.course_id = props.courses[0] ? String(props.courses[0].id) : '';
    form.teacher_id = props.teachers[0] ? String(props.teachers[0].id) : '';
    form.academic_period_id = activePeriodOptions.value[0]?.value ?? '';
    form.modality = 'in_person';
    form.classroom_id = props.classrooms[0] ? String(props.classrooms[0].id) : '';
    form.blocks = [
        initialBlock
            ? {
                  day_of_week: String(initialBlock.dayOfWeek),
                  starts_at: initialBlock.startsAt,
                  ends_at: initialBlock.endsAt,
              }
            : { day_of_week: '1', starts_at: '08:00', ends_at: '10:00' },
    ];
    modalOpen.value = true;
}

function openEdit(row: ScheduleRow): void {
    editingId.value = row.id;
    form.clearErrors();
    form.course_id = String(row.course_id);
    form.teacher_id = String(row.teacher_id);
    form.academic_period_id = String(row.academic_period_id);
    form.modality = row.modality;
    form.classroom_id = row.classroom_id ? String(row.classroom_id) : '';
    form.blocks = row.blocks.map((b) => ({
        day_of_week: String(b.day_of_week),
        starts_at: b.starts_at,
        ends_at: b.ends_at,
    }));
    modalOpen.value = true;
}

function addBlock(): void {
    form.blocks.push({ day_of_week: '1', starts_at: '08:00', ends_at: '10:00' });
}

function removeBlock(index: number): void {
    if (form.blocks.length > 1) {
        form.blocks.splice(index, 1);
    }
}

function saveSchedule(): void {
    const payload = {
        course_id: Number(form.course_id),
        teacher_id: Number(form.teacher_id),
        academic_period_id: Number(form.academic_period_id),
        modality: form.modality,
        classroom_id: form.modality === 'in_person' && form.classroom_id ? Number(form.classroom_id) : null,
        blocks: form.blocks.map((b) => ({
            day_of_week: Number(b.day_of_week),
            starts_at: b.starts_at,
            ends_at: b.ends_at,
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

function handleCalendarMove(move: CalendarBlockMove): void {
    const row = props.schedules.data.find((item) => item.id === move.scheduleId);
    if (!row || row.period_has_ended) {
        move.revert();
        return;
    }

    const updatedBlocks = row.blocks.map((block, idx) =>
        idx === move.blockIndex
            ? { day_of_week: move.dayOfWeek, starts_at: move.startsAt, ends_at: move.endsAt }
            : { day_of_week: block.day_of_week, starts_at: block.starts_at, ends_at: block.ends_at },
    );

    router.put(
        `/scheduling/schedules/${row.id}`,
        {
            course_id: row.course_id,
            teacher_id: row.teacher_id,
            academic_period_id: row.academic_period_id,
            modality: row.modality,
            classroom_id: row.classroom_id,
            blocks: updatedBlocks,
        },
        {
            preserveScroll: true,
            onError: () => move.revert(),
        },
    );
}

async function confirmDelete(row: ScheduleRow): Promise<void> {
    if (
        await requestAlertDialog({
            variant: 'danger',
            title: '¿Eliminar esta programación?',
            message: `Se quitará el horario de ${row.course_name} asignado a ${row.teacher_name}.`,
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
                    Gestiona horarios de cursos, docentes asignados y aulas por periodo académico.
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
                <Link
                    href="/scheduling/periods"
                    class="inline-flex min-h-11 items-center rounded-control border border-border bg-panel px-3 py-2 text-sm font-medium text-text hover:bg-surface"
                >
                    Periodos
                </Link>
                <Button @click="openCreate()">Nueva programación</Button>
            </div>
        </div>

        <form class="mb-6 flex flex-wrap items-end gap-3" role="search" @submit.prevent="goToPage(1)">
            <div class="min-w-48 flex-1">
                <label for="filter-q" class="mb-1 block text-xs text-text-muted">Buscar</label>
                <Input id="filter-q" v-model="filters.q" placeholder="Curso o docente" />
            </div>
            <div class="w-48">
                <label for="filter-period" class="mb-1 block text-xs text-text-muted">Periodo</label>
                <Select id="filter-period" v-model="filters.period" :options="periodFilterOptions" />
            </div>
            <div class="w-48">
                <label for="filter-course" class="mb-1 block text-xs text-text-muted">Curso</label>
                <Select id="filter-course" v-model="filters.course" :options="courseFilterOptions" />
            </div>
            <div class="w-48">
                <label for="filter-teacher" class="mb-1 block text-xs text-text-muted">Docente</label>
                <Select id="filter-teacher" v-model="filters.teacher" :options="teacherFilterOptions" />
            </div>
            <div class="w-48">
                <label for="filter-classroom" class="mb-1 block text-xs text-text-muted">Aula</label>
                <Select
                    id="filter-classroom"
                    v-model="filters.classroom"
                    :options="classroomFilterOptions"
                />
            </div>
            <IconButton icon="search" label="Filtrar horarios" type="submit" variant="primary" />
            <IconButton v-if="hasFilters" icon="clear" label="Limpiar filtros" @click="reset" />
        </form>

        <div class="mb-6">
            <ScheduleCalendar
                :blocks="calendarBlocks"
                :editable="true"
                @select="openCreate"
                @move="handleCalendarMove"
            />
        </div>

        <Table :columns="columns" :rows="schedules.data as unknown as Record<string, unknown>[]">
            <template #cell-course="{ row }">
                <p class="font-semibold">{{ row.course_name }}</p>
                <div class="mt-0.5 flex items-center gap-2 text-xs text-text-muted">
                    <span>{{ row.period_name }}</span>
                    <Badge v-if="row.period_has_ended" variant="neutral">Periodo cerrado</Badge>
                </div>
            </template>
            <template #cell-teacher="{ row }">{{ row.teacher_name }}</template>
            <template #cell-location="{ row }">
                <Badge :variant="row.modality === 'virtual' ? 'neutral' : 'success'">
                    {{ row.modality === 'virtual' ? 'Virtual' : row.classroom_name || 'Presencial' }}
                </Badge>
            </template>
            <template #cell-blocks="{ row }">
                <ul class="space-y-1 text-xs">
                    <li
                        v-for="(block, idx) in row.blocks as ScheduleBlockItem[]"
                        :key="idx"
                    >
                        {{ dayNames[block.day_of_week] }}: {{ block.starts_at }} – {{ block.ends_at }}
                    </li>
                </ul>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex items-center gap-2">
                    <IconButton
                        v-if="!row.period_has_ended"
                        icon="edit"
                        :label="`Editar programación de ${row.course_name}`"
                        variant="primary"
                        @click="openEdit(row as unknown as ScheduleRow)"
                    />
                    <IconButton
                        v-if="!row.period_has_ended"
                        icon="delete"
                        :label="`Eliminar programación de ${row.course_name}`"
                        variant="danger"
                        @click="confirmDelete(row as unknown as ScheduleRow)"
                    />
                    <span v-else class="text-xs text-text-muted">Solo lectura</span>
                </div>
            </template>
            <template #empty>
                <EmptyState
                    :title="hasFilters ? 'Sin resultados para los filtros' : 'Aún no hay programaciones'"
                    :description="
                        hasFilters
                            ? 'Prueba ajustando el curso, docente o aula filtrada.'
                            : 'Crea una programación académica o selecciona un rango en el calendario.'
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
            @close="modalOpen = false"
        >
            <form class="flex flex-col gap-4" @submit.prevent="saveSchedule">
                <FormField
                    id="schedule-period"
                    label="Periodo académico"
                    :error="form.errors.academic_period_id"
                >
                    <Select
                        id="schedule-period"
                        v-model="form.academic_period_id"
                        :options="activePeriodOptions"
                    />
                </FormField>

                <FormField id="schedule-course" label="Curso" :error="form.errors.course_id">
                    <Select id="schedule-course" v-model="form.course_id" :options="courseOptions" />
                </FormField>

                <FormField id="schedule-teacher" label="Docente" :error="form.errors.teacher_id">
                    <SearchableSelect
                        id="schedule-teacher"
                        v-model="form.teacher_id"
                        :options="teacherOptions"
                        :error="form.errors.teacher_id"
                        placeholder="Seleccione un docente"
                        search-placeholder="Buscar docente por nombre..."
                        empty-message="No hay docentes disponibles."
                        no-results-message="No se encontraron docentes."
                    />
                </FormField>

                <FormField id="schedule-modality" label="Modalidad" :error="form.errors.modality">
                    <Select
                        id="schedule-modality"
                        v-model="form.modality"
                        :options="modalityOptions"
                    />
                </FormField>

                <FormField
                    v-if="form.modality === 'in_person'"
                    id="schedule-classroom"
                    label="Aula física"
                    :error="form.errors.classroom_id"
                >
                    <Select
                        id="schedule-classroom"
                        v-model="form.classroom_id"
                        :options="classroomOptions"
                    />
                </FormField>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-text">Bloques de horario semanal</p>
                        <Button type="button" variant="secondary" @click="addBlock">
                            Añadir bloque
                        </Button>
                    </div>
                    <p v-if="(form.errors as Record<string, string>).blocks" class="text-xs text-danger">
                        {{ (form.errors as Record<string, string>).blocks }}
                    </p>
                    <div
                        v-for="(block, index) in form.blocks"
                        :key="index"
                        class="grid grid-cols-1 gap-2 rounded-control border border-border p-3 sm:grid-cols-4 sm:items-end"
                    >
                        <div>
                            <label :for="`block-day-${index}`" class="mb-1 block text-xs text-text-muted">
                                Día
                            </label>
                            <Select
                                :id="`block-day-${index}`"
                                v-model="block.day_of_week"
                                :options="dayOptions"
                            />
                        </div>
                        <div>
                            <label :for="`block-start-${index}`" class="mb-1 block text-xs text-text-muted">
                                Inicio
                            </label>
                            <Input :id="`block-start-${index}`" v-model="block.starts_at" type="time" />
                        </div>
                        <div>
                            <label :for="`block-end-${index}`" class="mb-1 block text-xs text-text-muted">
                                Fin
                            </label>
                            <Input :id="`block-end-${index}`" v-model="block.ends_at" type="time" />
                        </div>
                        <div class="flex justify-end">
                            <IconButton
                                v-if="form.blocks.length > 1"
                                icon="delete"
                                label="Quitar bloque"
                                variant="danger"
                                @click="removeBlock(index)"
                            />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <Button type="button" variant="secondary" @click="modalOpen = false">
                        Cancelar
                    </Button>
                    <Button type="submit" :loading="form.processing">Guardar programación</Button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>

