<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
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
import { useListFilters } from '@/services/listFilters';
import { submit } from '@/services/http';

interface Student {
    id: number;
    name: string;
    username: string | null;
    identifier: string | null;
    birth_date: string | null;
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
    students: { data: Student[]; meta: PageMeta };
    filters: { q: string; status: string };
    canManageStudents: boolean;
}>();

const { filters, goToPage, reset } = useListFilters('/students', {
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
});
const hasFilters = computed(() => filters.q !== '' || filters.status !== '');
const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'active', label: 'Activos' },
    { value: 'inactive', label: 'Inactivos' },
];
const columns = [
    { key: 'name', label: 'Estudiante' },
    { key: 'username', label: 'Usuario' },
    { key: 'identifier', label: 'Documento / Identificador' },
    { key: 'birth_date', label: 'Fecha de nacimiento' },
    { key: 'is_active', label: 'Estado' },
    { key: 'actions', label: 'Acciones' },
];
const creating = ref(false);
const form = useForm({
    name: '',
    identifier: '',
    username: '',
    birth_date: '',
    temporary_password: '',
});

function register() {
    form.post('/students', {
        preserveScroll: true,
        onSuccess: () => {
            creating.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <Head title="Estudiantes" />
    <AppLayout>
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Estudiantes</h1>
            <Button v-if="canManageStudents" @click="creating = true">Registrar estudiante</Button>
        </div>

        <form class="mb-4 flex flex-wrap items-end gap-3" role="search" @submit.prevent="goToPage(1)">
            <div class="min-w-56 flex-1">
                <label for="filter-q" class="mb-1 block text-xs text-text-muted">Buscar</label>
                <Input
                    id="filter-q"
                    v-model="filters.q"
                    placeholder="Nombre, usuario, documento o correo"
                />
            </div>
            <div class="w-48">
                <label for="filter-status" class="mb-1 block text-xs text-text-muted">Estado</label>
                <Select id="filter-status" v-model="filters.status" :options="statusOptions" />
            </div>
            <IconButton icon="search" label="Buscar" type="submit" variant="primary" />
            <IconButton v-if="hasFilters" icon="clear" label="Limpiar filtros" @click="reset" />
        </form>

        <Table :columns="columns" :rows="students.data as unknown as Record<string, unknown>[]">
            <template #cell-name="{ row }">
                <p class="font-semibold">{{ row.name }}</p>
                <p class="text-xs text-text-muted">@{{ row.username }}</p>
            </template>
            <template #cell-username="{ row }">{{ row.username || '—' }}</template>
            <template #cell-identifier="{ row }">{{ row.identifier || '—' }}</template>
            <template #cell-birth_date="{ row }">{{ row.birth_date || '—' }}</template>
            <template #cell-is_active="{ row }">
                <Badge :variant="row.is_active ? 'success' : 'neutral'">{{
                    row.is_active ? 'Activo' : 'Inactivo'
                }}</Badge>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex items-center gap-2">
                    <IconButton
                        icon="view"
                        label="Ver estudiante"
                        :href="`/students/${row.id}`"
                        variant="primary"
                    />
                    <IconButton
                        v-if="canManageStudents"
                        :icon="row.is_active ? 'deactivate' : 'activate'"
                        :label="row.is_active ? 'Desactivar' : 'Activar'"
                        :variant="row.is_active ? 'danger' : 'success'"
                        @click="submit('patch', `/students/${row.id}/status`, { is_active: !row.is_active })"
                    />
                </div>
            </template>
            <template #empty>
                <EmptyState
                    :title="hasFilters ? 'Sin resultados' : 'Aún no hay estudiantes'"
                    :description="
                        hasFilters
                            ? 'Prueba con otros filtros.'
                            : 'Los estudiantes registrados aparecerán aquí.'
                    "
                />
            </template>
        </Table>
        <Pagination
            class="mt-4"
            :page="students.meta.page"
            :last-page="students.meta.last_page"
            :total="students.meta.total"
            :from="students.meta.from"
            :to="students.meta.to"
            @change="goToPage"
        />

        <Modal :open="creating" title="Registrar estudiante" @close="creating = false">
            <form class="flex flex-col gap-3" @submit.prevent="register">
                <FormField id="name" label="Nombre completo" :error="form.errors.name">
                    <Input id="name" v-model="form.name" :error="form.errors.name" />
                </FormField>
                <FormField id="identifier" label="Documento / Identificador" :error="form.errors.identifier">
                    <Input id="identifier" v-model="form.identifier" :error="form.errors.identifier" />
                </FormField>
                <FormField id="username" label="Nombre de usuario" :error="form.errors.username">
                    <Input id="username" v-model="form.username" :error="form.errors.username" />
                </FormField>
                <FormField id="birth_date" label="Fecha de nacimiento" :error="form.errors.birth_date">
                    <Input id="birth_date" v-model="form.birth_date" type="date" :error="form.errors.birth_date" />
                </FormField>
                <FormField
                    id="temporary_password"
                    label="Contraseña inicial"
                    :error="form.errors.temporary_password"
                >
                    <Input
                        id="temporary_password"
                        v-model="form.temporary_password"
                        type="password"
                        :error="form.errors.temporary_password"
                    />
                </FormField>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <Button type="button" variant="secondary" @click="creating = false">
                        Cancelar
                    </Button>
                    <Button type="submit" :loading="form.processing">Registrar</Button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
