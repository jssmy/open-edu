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
import Table from '@/design-system/Table.vue';
import { requestAlertDialog } from '@/composables/useAlertDialog';
import { useListFilters } from '@/services/listFilters';

interface PeriodRow {
    id: number;
    name: string;
    starts_on: string;
    ends_on: string;
    has_ended: boolean;
}

interface PageMeta {
    page: number;
    last_page: number;
    total: number;
    from: number;
    to: number;
}

const props = defineProps<{
    periods: { data: PeriodRow[]; meta: PageMeta };
    filters: { q: string };
}>();

const { filters, goToPage, reset } = useListFilters('/scheduling/periods', {
    q: props.filters.q ?? '',
});
const hasFilters = computed(() => filters.q !== '');

const columns = [
    { key: 'name', label: 'Periodo académico' },
    { key: 'dates', label: 'Vigencia' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: 'Acciones' },
];

const modalOpen = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({
    name: '',
    starts_on: '',
    ends_on: '',
});

function openCreate(): void {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    modalOpen.value = true;
}

function openEdit(row: PeriodRow): void {
    editingId.value = row.id;
    form.clearErrors();
    form.name = row.name;
    form.starts_on = row.starts_on;
    form.ends_on = row.ends_on;
    modalOpen.value = true;
}

function savePeriod(): void {
    const url = editingId.value ? `/scheduling/periods/${editingId.value}` : '/scheduling/periods';
    const method = editingId.value ? 'put' : 'post';

    form[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            modalOpen.value = false;
        },
    });
}

async function confirmDelete(row: PeriodRow): Promise<void> {
    if (
        await requestAlertDialog({
            variant: 'danger',
            title: '¿Eliminar periodo académico?',
            message: `Se eliminará el periodo ${row.name} si no tiene programaciones.`,
            confirmLabel: 'Eliminar periodo',
        })
    ) {
        router.delete(`/scheduling/periods/${row.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Periodos académicos" />
    <AppLayout>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <Link href="/scheduling" class="text-sm text-text-muted hover:text-primary">
                    ← Programación académica
                </Link>
                <h1 class="mt-1 text-xl font-semibold">Periodos académicos</h1>
            </div>
            <Button @click="openCreate">Nuevo periodo</Button>
        </div>

        <form class="mb-4 flex flex-wrap items-end gap-3" role="search" @submit.prevent="goToPage(1)">
            <div class="min-w-56 flex-1">
                <label for="filter-q" class="mb-1 block text-xs text-text-muted">Buscar</label>
                <Input id="filter-q" v-model="filters.q" placeholder="Nombre del periodo" />
            </div>
            <IconButton icon="search" label="Buscar periodos" type="submit" variant="primary" />
            <IconButton v-if="hasFilters" icon="clear" label="Limpiar filtros" @click="reset" />
        </form>

        <Table :columns="columns" :rows="periods.data as unknown as Record<string, unknown>[]">
            <template #cell-name="{ row }">
                <span class="font-semibold">{{ row.name }}</span>
            </template>
            <template #cell-dates="{ row }">{{ row.starts_on }} → {{ row.ends_on }}</template>
            <template #cell-status="{ row }">
                <Badge :variant="row.has_ended ? 'neutral' : 'success'">
                    {{ row.has_ended ? 'Cerrado (solo lectura)' : 'Vigente' }}
                </Badge>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex items-center gap-2">
                    <IconButton
                        v-if="!row.has_ended"
                        icon="edit"
                        :label="`Editar ${row.name}`"
                        variant="primary"
                        @click="openEdit(row as unknown as PeriodRow)"
                    />
                    <IconButton
                        v-if="!row.has_ended"
                        icon="delete"
                        :label="`Eliminar ${row.name}`"
                        variant="danger"
                        @click="confirmDelete(row as unknown as PeriodRow)"
                    />
                    <span v-else class="text-xs text-text-muted">Bloqueado</span>
                </div>
            </template>
            <template #empty>
                <EmptyState
                    :title="hasFilters ? 'Sin resultados para los filtros' : 'Aún no hay periodos académicos'"
                    :description="
                        hasFilters
                            ? 'Prueba con otro término de búsqueda.'
                            : 'Crea un periodo académico con fecha de inicio y fin para programar horarios.'
                    "
                />
            </template>
        </Table>

        <Pagination
            class="mt-4"
            :page="periods.meta.page"
            :last-page="periods.meta.last_page"
            :total="periods.meta.total"
            :from="periods.meta.from"
            :to="periods.meta.to"
            @change="goToPage"
        />

        <Modal
            :open="modalOpen"
            :title="editingId ? 'Editar periodo académico' : 'Nuevo periodo académico'"
            @close="modalOpen = false"
        >
            <form class="flex flex-col gap-3" @submit.prevent="savePeriod">
                <FormField id="period-name" label="Nombre" :error="form.errors.name">
                    <Input id="period-name" v-model="form.name" :error="form.errors.name" />
                </FormField>
                <FormField id="period-starts" label="Fecha de inicio" :error="form.errors.starts_on">
                    <Input
                        id="period-starts"
                        v-model="form.starts_on"
                        type="date"
                        :error="form.errors.starts_on"
                    />
                </FormField>
                <FormField id="period-ends" label="Fecha de fin" :error="form.errors.ends_on">
                    <Input
                        id="period-ends"
                        v-model="form.ends_on"
                        type="date"
                        :error="form.errors.ends_on"
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

