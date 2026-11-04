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
import Select from '@/design-system/Select.vue';
import Table from '@/design-system/Table.vue';
import { requestAlertDialog } from '@/composables/useAlertDialog';
import { useListFilters } from '@/services/listFilters';

interface ClassroomRow {
    id: number;
    name: string;
    capacity: number | null;
    is_active: boolean;
}

interface PageMeta {
    page: number;
    last_page: number;
    total: number;
    from: number;
    to: number;
}

const props = defineProps<{
    classrooms: { data: ClassroomRow[]; meta: PageMeta };
    filters: { q: string; status: string };
}>();

const { filters, goToPage, reset } = useListFilters('/scheduling/classrooms', {
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
});
const hasFilters = computed(() => filters.q !== '' || filters.status !== '');

const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'active', label: 'Activas' },
    { value: 'inactive', label: 'Inactivas' },
];

const columns = [
    { key: 'name', label: 'Aula' },
    { key: 'capacity', label: 'Capacidad' },
    { key: 'is_active', label: 'Estado' },
    { key: 'actions', label: 'Acciones' },
];

const modalOpen = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({
    name: '',
    capacity: '',
    is_active: true,
});

function openCreate(): void {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    modalOpen.value = true;
}

function openEdit(row: ClassroomRow): void {
    editingId.value = row.id;
    form.clearErrors();
    form.name = row.name;
    form.capacity = row.capacity ? String(row.capacity) : '';
    form.is_active = row.is_active;
    modalOpen.value = true;
}

function saveClassroom(): void {
    const payload = {
        name: form.name,
        capacity: form.capacity ? Number(form.capacity) : null,
        is_active: form.is_active,
    };
    const url = editingId.value
        ? `/scheduling/classrooms/${editingId.value}`
        : '/scheduling/classrooms';
    const method = editingId.value ? 'put' : 'post';

    form.transform(() => payload)[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            modalOpen.value = false;
        },
    });
}

async function confirmDelete(row: ClassroomRow): Promise<void> {
    if (
        await requestAlertDialog({
            variant: 'danger',
            title: '¿Eliminar aula?',
            message: `Se eliminará el aula ${row.name} si no tiene programaciones asociadas.`,
            confirmLabel: 'Eliminar aula',
        })
    ) {
        router.delete(`/scheduling/classrooms/${row.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Aulas" />
    <AppLayout>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <Link href="/scheduling" class="text-sm text-text-muted hover:text-primary">
                    ← Programación académica
                </Link>
                <h1 class="mt-1 text-xl font-semibold">Catálogo de aulas</h1>
            </div>
            <Button @click="openCreate">Nueva aula</Button>
        </div>

        <form class="mb-4 flex flex-wrap items-end gap-3" role="search" @submit.prevent="goToPage(1)">
            <div class="min-w-56 flex-1">
                <label for="filter-q" class="mb-1 block text-xs text-text-muted">Buscar</label>
                <Input id="filter-q" v-model="filters.q" placeholder="Nombre del aula" />
            </div>
            <div class="w-48">
                <label for="filter-status" class="mb-1 block text-xs text-text-muted">Estado</label>
                <Select id="filter-status" v-model="filters.status" :options="statusOptions" />
            </div>
            <IconButton icon="search" label="Buscar aulas" type="submit" variant="primary" />
            <IconButton v-if="hasFilters" icon="clear" label="Limpiar filtros" @click="reset" />
        </form>

        <Table :columns="columns" :rows="classrooms.data as unknown as Record<string, unknown>[]">
            <template #cell-name="{ row }">
                <span class="font-semibold">{{ row.name }}</span>
            </template>
            <template #cell-capacity="{ row }">{{ row.capacity ?? '—' }}</template>
            <template #cell-is_active="{ row }">
                <Badge :variant="row.is_active ? 'success' : 'neutral'">
                    {{ row.is_active ? 'Activa' : 'Inactiva' }}
                </Badge>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex items-center gap-2">
                    <IconButton
                        icon="edit"
                        :label="`Editar ${row.name}`"
                        variant="primary"
                        @click="openEdit(row as unknown as ClassroomRow)"
                    />
                    <IconButton
                        icon="delete"
                        :label="`Eliminar ${row.name}`"
                        variant="danger"
                        @click="confirmDelete(row as unknown as ClassroomRow)"
                    />
                </div>
            </template>
            <template #empty>
                <EmptyState
                    :title="hasFilters ? 'Sin resultados para los filtros' : 'Aún no hay aulas registradas'"
                    :description="
                        hasFilters
                            ? 'Prueba con otra búsqueda.'
                            : 'Registra aulas físicas para asignarlas en horarios presenciales.'
                    "
                />
            </template>
        </Table>

        <Pagination
            class="mt-4"
            :page="classrooms.meta.page"
            :last-page="classrooms.meta.last_page"
            :total="classrooms.meta.total"
            :from="classrooms.meta.from"
            :to="classrooms.meta.to"
            @change="goToPage"
        />

        <Modal
            :open="modalOpen"
            :title="editingId ? 'Editar aula' : 'Nueva aula'"
            @close="modalOpen = false"
        >
            <form class="flex flex-col gap-3" @submit.prevent="saveClassroom">
                <FormField id="classroom-name" label="Nombre del aula" :error="form.errors.name">
                    <Input id="classroom-name" v-model="form.name" :error="form.errors.name" />
                </FormField>
                <FormField id="classroom-capacity" label="Capacidad (opcional)" :error="form.errors.capacity">
                    <Input
                        id="classroom-capacity"
                        v-model="form.capacity"
                        type="number"
                        :error="form.errors.capacity"
                    />
                </FormField>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <Button type="button" variant="secondary" @click="modalOpen = false">
                        Cancelar
                    </Button>
                    <Button type="submit" :loading="form.processing">Guardar</Button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>

