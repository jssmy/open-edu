<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
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

interface UserRow {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    role_label: string | null;
}

const props = defineProps<{
    users: {
        data: UserRow[];
        meta: { page: number; last_page: number; total: number; from: number; to: number };
    };
    filters: { q: string; role: string; status: string };
    roles: { value: string; label: string }[];
}>();

const { filters, goToPage, reset } = useListFilters('/users', {
    q: props.filters.q ?? '',
    role: props.filters.role ?? '',
    status: props.filters.status ?? '',
});
const hasFilters = computed(() => filters.q !== '' || filters.role !== '' || filters.status !== '');
const roleFilterOptions = computed(() => [{ value: '', label: 'Todos los roles' }, ...props.roles]);
const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'active', label: 'Activos' },
    { value: 'inactive', label: 'Inactivos' },
];

const creating = ref(false);
const form = useForm({ name: '', email: '', role: 'docente', password: '' });
const columns = [
    { key: 'name', label: 'Nombre' },
    { key: 'email', label: 'Correo' },
    { key: 'role_label', label: 'Rol' },
    { key: 'is_active', label: 'Estado' },
    { key: 'actions', label: 'Acciones' },
];

function create() {
    form.post('/users', {
        onSuccess: () => {
            creating.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <Head title="Usuarios" />
    <AppLayout>
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Usuarios</h1>
            <Button @click="creating = true">Nuevo usuario</Button>
        </div>
        <Alert v-if="$page.props.errors?.general" variant="danger" class="mb-4">{{
            $page.props.errors.general
        }}</Alert>
        <form class="mb-4 flex flex-wrap items-end gap-3" role="search" @submit.prevent="goToPage(1)">
            <div class="min-w-56 flex-1">
                <label for="filter-q" class="mb-1 block text-xs text-text-muted">Buscar</label>
                <Input id="filter-q" v-model="filters.q" placeholder="Nombre o correo" />
            </div>
            <div class="w-48">
                <label for="filter-role" class="mb-1 block text-xs text-text-muted">Rol</label>
                <Select id="filter-role" v-model="filters.role" :options="roleFilterOptions" />
            </div>
            <div class="w-48">
                <label for="filter-status" class="mb-1 block text-xs text-text-muted">Estado</label>
                <Select id="filter-status" v-model="filters.status" :options="statusOptions" />
            </div>
            <IconButton icon="search" label="Buscar" type="submit" variant="primary" />
            <IconButton v-if="hasFilters" icon="clear" label="Limpiar filtros" @click="reset" />
        </form>
        <Table :columns="columns" :rows="users.data as unknown as Record<string, unknown>[]">
            <template #cell-is_active="{ row }">
                <Badge :variant="row.is_active ? 'success' : 'neutral'">{{
                    row.is_active ? 'Activo' : 'Inactivo'
                }}</Badge>
            </template>
            <template #cell-actions="{ row }">
                <div class="flex items-center gap-2">
                    <IconButton
                        icon="edit"
                        label="Editar usuario"
                        :href="`/users/${row.id}`"
                        variant="primary"
                    />
                    <IconButton
                        :icon="row.is_active ? 'deactivate' : 'activate'"
                        :label="row.is_active ? 'Desactivar' : 'Activar'"
                        :variant="row.is_active ? 'danger' : 'success'"
                        @click="submit('patch', `/users/${row.id}/status`, { is_active: !row.is_active })"
                    />
                </div>
            </template>
            <template #empty
                ><EmptyState :title="hasFilters ? 'Sin resultados' : 'Aún no hay usuarios'"
            /></template>
        </Table>
        <Pagination
            class="mt-4"
            :page="users.meta.page"
            :last-page="users.meta.last_page"
            :total="users.meta.total"
            :from="users.meta.from"
            :to="users.meta.to"
            @change="goToPage"
        />

        <Modal :open="creating" title="Nuevo usuario" @close="creating = false">
            <form id="create-user" class="flex flex-col gap-3" @submit.prevent="create">
                <FormField id="name" label="Nombre" :error="form.errors.name">
                    <Input id="name" v-model="form.name" :error="form.errors.name" />
                </FormField>
                <FormField id="email" label="Correo" :error="form.errors.email">
                    <Input id="email" v-model="form.email" type="email" :error="form.errors.email" />
                </FormField>
                <FormField id="role" label="Rol" :error="form.errors.role">
                    <Select id="role" v-model="form.role" :options="roles" />
                </FormField>
                <FormField id="password" label="Contraseña temporal" :error="form.errors.password">
                    <Input
                        id="password"
                        v-model="form.password"
                        type="password"
                        :error="form.errors.password"
                    />
                </FormField>
            </form>
            <template #actions>
                <Button variant="secondary" @click="creating = false">Cancelar</Button>
                <Button type="submit" :loading="form.processing" @click="create">Crear</Button>
            </template>
        </Modal>
    </AppLayout>
</template>
