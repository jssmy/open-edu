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
    group_code: string;
    teacher_id: number;
    teacher_ids: number[];
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
    classrooms: { id: number; name: string; capacity?: number | null }[];
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

const modalityOptions = [
    { value: 'in_person', label: 'Presencial' },
    { value: 'virtual', label: 'Virtual' },
    { value: 'hybrid', label: 'Híbrida' },
];

const classroomOptions = computed(() => [
    { value: '', label: 'Seleccione un aula' },
    ...props.classrooms.map((c) => ({ value: String(c.id), label: c.name })),
]);

const todayIso = new Date().toISOString().slice(0, 10);

const selectedRow = ref<PendingRow | null>(null);
const form = useForm({
    group_code: 'GRP-01',
    starts_on: todayIso,
    ends_on: '2026-12-31',
    max_students: '30',
    modality: 'in_person' as 'in_person' | 'virtual' | 'hybrid',
    classroom_id: props.classrooms[0] ? String(props.classrooms[0].id) : '',
    session_title: 'Sesión 1',
    session_date: todayIso,
    starts_at: '08:00',
    ends_at: '10:00',
});

function openComplete(row: PendingRow): void {
    selectedRow.value = row;
    form.group_code = `GRP-${String(row.id).padStart(2, '0')}`;
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
            group_code: '',
            starts_on: form.starts_on,
            ends_on: form.ends_on,
            max_students: Number(form.max_students),
            modality: form.modality,
            status: 'published',
            teacher_ids: row.teacher_ids?.length ? row.teacher_ids : [row.teacher_id],
            sessions: [
                {
                    title: form.session_title,
                    session_date: form.session_date,
                    starts_at: form.starts_at,
                    ends_at: form.ends_at,
                    teacher_id: row.teacher_id,
                    classroom_id:
                        form.modality !== 'virtual' && form.classroom_id
                            ? Number(form.classroom_id)
                            : null,
                    status: 'scheduled',
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
                    Docentes migrados pendientes de grupo, fechas, modalidad y sesiones.
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
                            : 'Todas las asignaciones docentes cuentan con programación.'
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
            size="2xl"
            @close="selectedRow = null"
        >
            <form class="flex flex-col gap-3" @submit.prevent="submitComplete">
                <div class="grid grid-cols-2 gap-3">
                    <FormField id="pending-group" label="Código del grupo (autogenerado)">
                        <Input id="pending-group" v-model="form.group_code" :disabled="true" />
                    </FormField>
                    <FormField id="pending-max" label="Cupos máximos">
                        <Input id="pending-max" v-model="form.max_students" type="number" min="1" />
                    </FormField>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <FormField id="pending-starts" label="Fecha de inicio">
                        <Input id="pending-starts" v-model="form.starts_on" type="date" />
                    </FormField>
                    <FormField id="pending-ends" label="Fecha de fin">
                        <Input id="pending-ends" v-model="form.ends_on" type="date" />
                    </FormField>
                </div>
                <FormField id="pending-modality" label="Modalidad">
                    <Select
                        id="pending-modality"
                        v-model="form.modality"
                        :options="modalityOptions"
                    />
                </FormField>
                <FormField
                    v-if="form.modality !== 'virtual'"
                    id="pending-classroom"
                    label="Aula física"
                >
                    <Select
                        id="pending-classroom"
                        v-model="form.classroom_id"
                        :options="classroomOptions"
                    />
                </FormField>
                <FormField id="pending-session-date" label="Fecha de la sesión">
                    <Input id="pending-session-date" v-model="form.session_date" type="date" />
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
