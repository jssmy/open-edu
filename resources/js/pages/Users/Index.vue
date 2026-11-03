<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import FormField from '@/design-system/FormField.vue';
import Input from '@/design-system/Input.vue';
import Modal from '@/design-system/Modal.vue';
import Select from '@/design-system/Select.vue';
import Table from '@/design-system/Table.vue';
import { submit } from '@/services/http';

interface UserRow {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    role_label: string | null;
}

defineProps<{ users: UserRow[]; roles: { value: string; label: string }[] }>();

const creating = ref(false);
const form = useForm({ name: '', email: '', role: 'docente', password: '' });
const columns = [
    { key: 'name', label: 'Nombre' },
    { key: 'email', label: 'Correo' },
    { key: 'role_label', label: 'Rol' },
    { key: 'is_active', label: 'Estado' },
    { key: 'actions', label: '' },
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
        <Table :columns="columns" :rows="users as unknown as Record<string, unknown>[]">
            <template #cell-is_active="{ row }">
                <Badge :variant="row.is_active ? 'success' : 'neutral'">{{
                    row.is_active ? 'Activo' : 'Inactivo'
                }}</Badge>
            </template>
            <template #cell-actions="{ row }">
                <Link :href="`/users/${row.id}`" class="mr-3 text-primary">Editar</Link>
                <button
                    type="button"
                    class="text-text-muted"
                    @click="submit('patch', `/users/${row.id}/status`, { is_active: !row.is_active })"
                >
                    {{ row.is_active ? 'Desactivar' : 'Activar' }}
                </button>
            </template>
            <template #empty><EmptyState title="Aún no hay usuarios" /></template>
        </Table>

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
