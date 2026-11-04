<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Button from '@/design-system/Button.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import FormField from '@/design-system/FormField.vue';
import IconButton from '@/design-system/IconButton.vue';
import Input from '@/design-system/Input.vue';
import Modal from '@/design-system/Modal.vue';
import Pagination from '@/design-system/Pagination.vue';
import Select from '@/design-system/Select.vue';
import Table from '@/design-system/Table.vue';
import { useListFilters } from '@/services/listFilters';

interface PendingRow {
    id: number;
    course_id: number;
    course_name: string;
    teacher_id: number;
    teacher_name: string;
}

interface PageMeta {
    page: number;
    last_page: number;
    total: number;
    from: number;
    to: number;
}

const props = defineProps<{
    pending: { data: PendingRow[]; meta: PageMeta };
    filters: { q: string };
    classrooms: { id: number; name: string }[];
    periods: { id: number; name: string }[];
}>();

const { filters, goToPage, reset } = useListFilters('/scheduling/pending', {
    q: props.filters.q ?? '',
});
const hasFilters = computed(() => filters.q !== '');

const columns = [
    { key: 'course', label: 'Curso' },
    { key: 'teacher', label: 'Docente asignado' },
    { key: 'actions', label: 'Acciones' },
];

const dayOptions = [
    { value: '1', label: 'Lunes' },
    { value: '2', label: 'Martes' },
    { value: '3', label: 'Miércoles' },
    { value: '4', label: 'Jueves' },
    { value: '5', label: 'Viernes' },
    { value: '6', label: 'Sábado' },
    { value: '7', label: 'Domingo' },
];

const modalityOptions = [
    { value: 'in_person', label: 'Presencial' },
    { value: 'virtual', label: 'Virtual' },
];

const periodOptions = computed(() =>
    props.periods.map((p) => ({ value: String(p.id), label: p.name })),
);
const classroomOptions = computed(() => [
    { value: '', label: 'Seleccione un aula' },
    ...props.classrooms.map((c) => ({ value: String(c.id), label: c.name })),
]);

const selectedRow = ref<PendingRow | null>(null);
const form = useForm({
    academic_period_id: props.periods[0] ? String(props.periods[0].id) : '',
    modality: 'in_person' as 'in_person' | 'virtual',
    classroom_id: props.classrooms[0] ? String(props.classrooms[0].id) : '',
    day_of_week: '1',
    starts_at: '08:00',
    ends_at: '10:00',
});

function openComplete(row: PendingRow): void {
    selectedRow.value = row;
    form.clearErrors();
}

function submitComplete(): void {
    if (!selectedRow.value) {
        return;
    }

    const row = selectedRow.value;
    form
        .transform(() => ({
            course_id: row.course_id,
            teacher_id: row.teacher_id,
            academic_period_id: Number(form.academic_period_id),
            modality: form.modality,
            classroom_id:
                form.modality === 'in_person' && form.classroom_id
                    ? Number(form.classroom_id)
                    : null,
            blocks: [
                {
                    day_of_week: Number(form.day_of_week),
                    starts_at: form.starts_at,
                    ends_at: form.ends_at,
                },
            ],
        }))
        .put(`/scheduling/schedules/${row.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                selectedRow.value = null;
            },
        });
}
</script>

<template>
    <Head title="Por programar" />
    <AppLayout>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <Link href="/scheduling" class="text-sm text-text-muted hover:text-primary">
                    ← Programación académica
                </Link>
                <h1 class="mt-1 text-xl font-semibold">Asignaciones por programar</h1>
                <p class="text-sm text-text-muted">
                    Docentes migrados pendientes de periodo académico, modalidad y horario.
                </p>
            </div>
        </div>

        <form class="mb-4 flex flex-wrap items-end gap-3" role="search" @submit.prevent="goToPage(1)">
            <div class="min-w-56 flex-1">
                <label for="filter-q" class="mb-1 block text-xs text-text-muted">Buscar</label>
                <Input id="filter-q" v-model="filters.q" placeholder="Curso o docente" />
            </div>
            <IconButton icon="search" label="Buscar pendientes" type="submit" variant="primary" />
            <IconButton v-if="hasFilters" icon="clear" label="Limpiar filtros" @click="reset" />
        </form>

        <Table :columns="columns" :rows="pending.data as unknown as Record<string, unknown>[]">
            <template #cell-course="{ row }">
                <span class="font-semibold">{{ row.course_name }}</span>
            </template>
            <template #cell-teacher="{ row }">{{ row.teacher_name }}</template>
            <template #cell-actions="{ row }">
                <IconButton
                    icon="edit"
                    :label="`Programar ${row.course_name}`"
                    show-label
                    text="Completar horario"
                    variant="primary"
                    @click="openComplete(row as unknown as PendingRow)"
                />
            </template>
            <template #empty>
                <EmptyState
                    :title="
                        hasFilters
                            ? 'Sin resultados para los filtros'
                            : 'No hay asignaciones pendientes de horario'
                    "
                    :description="
                        hasFilters
                            ? 'Prueba con otro curso o docente.'
                            : 'Todas las asignaciones docentes cuentan con horario programado.'
                    "
                />
            </template>
        </Table>

        <Pagination
            class="mt-4"
            :page="pending.meta.page"
            :last-page="pending.meta.last_page"
            :total="pending.meta.total"
            :from="pending.meta.from"
            :to="pending.meta.to"
            @change="goToPage"
        />

        <Modal
            :open="selectedRow !== null"
            title="Completar programación académica"
            @close="selectedRow = null"
        >
            <form class="flex flex-col gap-3" @submit.prevent="submitComplete">
                <FormField id="pending-period" label="Periodo académico">
                    <Select
                        id="pending-period"
                        v-model="form.academic_period_id"
                        :options="periodOptions"
                    />
                </FormField>
                <FormField id="pending-modality" label="Modalidad">
                    <Select
                        id="pending-modality"
                        v-model="form.modality"
                        :options="modalityOptions"
                    />
                </FormField>
                <FormField
                    v-if="form.modality === 'in_person'"
                    id="pending-classroom"
                    label="Aula física"
                >
                    <Select
                        id="pending-classroom"
                        v-model="form.classroom_id"
                        :options="classroomOptions"
                    />
                </FormField>
                <FormField id="pending-day" label="Día">
                    <Select id="pending-day" v-model="form.day_of_week" :options="dayOptions" />
                </FormField>
                <div class="grid grid-cols-2 gap-3">
                    <FormField id="pending-start" label="Hora de inicio">
                        <Input id="pending-start" v-model="form.starts_at" type="time" />
                    </FormField>
                    <FormField id="pending-end" label="Hora de fin">
                        <Input id="pending-end" v-model="form.ends_at" type="time" />
                    </FormField>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <Button type="button" variant="secondary" @click="selectedRow = null">
                        Cancelar
                    </Button>
                    <Button type="submit" :loading="form.processing">Guardar programación</Button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>

