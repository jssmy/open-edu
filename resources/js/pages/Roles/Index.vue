<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import Card from '@/design-system/Card.vue';
import Checkbox from '@/design-system/Checkbox.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import IconButton from '@/design-system/IconButton.vue';
import Input from '@/design-system/Input.vue';
import Modal from '@/design-system/Modal.vue';
import Select from '@/design-system/Select.vue';
import Table from '@/design-system/Table.vue';
import { notifyError, notifySuccess, requestAlertDialog } from '@/composables/useAlertDialog';
import { useListFilters } from '@/services/listFilters';
import { submit } from '@/services/http';
import type { PermissionItem } from '@/types';

interface RoleRow extends Record<string, unknown> {
    id: number;
    name: string;
    label: string;
    description: string | null;
    is_active: boolean;
    is_system: boolean;
    locked: boolean;
    user_count: number;
    permissions: string[];
}

const props = defineProps<{
    roles: RoleRow[];
    filters: { q: string; status: string };
    permissions: PermissionItem[];
}>();

const { filters, reset, goToPage } = useListFilters('/roles', {
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
});

const hasFilters = computed(() => filters.q !== '' || filters.status !== '');

const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'active', label: 'Solo activos' },
    { value: 'inactive', label: 'Solo inactivos' },
];

const columns = [
    { key: 'label', label: 'Rol' },
    { key: 'description', label: 'Descripción' },
    { key: 'user_count', label: 'Usuarios' },
    { key: 'status', label: 'Estado' },
    { key: 'actions', label: 'Acciones' },
];

// Agrupación de permisos por módulo para el modal
const groupedPermissions = computed(() => {
    const groups: Record<string, PermissionItem[]> = {};
    for (const p of props.permissions) {
        const mod = p.module || 'General';
        if (!groups[mod]) {
            groups[mod] = [];
        }
        groups[mod].push(p);
    }
    return groups;
});

// Modal para configurar permisos
const editingRole = ref<RoleRow | null>(null);
const selectedPermissions = ref<string[]>([]);
const isSavingPermissions = ref(false);

function openPermissionsModal(role: RoleRow) {
    editingRole.value = role;
    selectedPermissions.value = [...role.permissions];
}

function togglePermission(permName: string, checked: boolean) {
    if (checked) {
        if (!selectedPermissions.value.includes(permName)) {
            selectedPermissions.value.push(permName);
        }
    } else {
        selectedPermissions.value = selectedPermissions.value.filter((item) => item !== permName);
    }
}

function savePermissions() {
    if (!editingRole.value) return;
    isSavingPermissions.value = true;
    router.put(
        `/roles/${editingRole.value.id}/permissions`,
        { permissions: selectedPermissions.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                isSavingPermissions.value = false;
                editingRole.value = null;
                void notifySuccess('Los permisos del rol han sido guardados correctamente.');
            },
            onError: (errors) => {
                isSavingPermissions.value = false;
                void notifyError(errors.general ?? errors.permissions ?? 'No se pudieron actualizar los permisos del rol.');
            },
        },
    );
}

// Inactivar / Reactivar rol
async function toggleRoleStatus(role: RoleRow) {
    if (role.is_active) {
        let confirm = false;
        if (role.user_count > 0) {
            const confirmed = await requestAlertDialog({
                variant: 'warning',
                title: '¿Inactivar rol?',
                message: `Este rol tiene ${role.user_count} usuario(s) asignado(s). Al inactivarlo, no podrán iniciar sesión hasta que se vuelva a activar. ¿Deseas continuar?`,
                confirmLabel: 'Inactivar rol',
                cancelLabel: 'Cancelar',
            });
            if (!confirmed) return;
            confirm = true;
        } else {
            const confirmed = await requestAlertDialog({
                variant: 'warning',
                title: '¿Inactivar rol?',
                message: `¿Estás seguro de inactivar el rol "${role.label}"? No se podrá asignar a nuevos usuarios.`,
                confirmLabel: 'Inactivar',
                cancelLabel: 'Cancelar',
            });
            if (!confirmed) return;
        }

        submit('put', `/roles/${role.id}/status`, { active: false, confirm });
    } else {
        submit('put', `/roles/${role.id}/status`, { active: true });
    }
}
</script>

<template>
    <Head title="Roles y permisos" />
    <AppLayout>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold">Roles y permisos</h1>
                <p class="text-sm text-text-muted">
                    Gestiona los roles del sistema, su disponibilidad y los permisos asignados con nombres descriptivos.
                </p>
            </div>
            <Link href="/roles/create">
                <Button variant="primary">Nuevo rol</Button>
            </Link>
        </div>

        <Alert v-if="$page.props.errors?.general" variant="danger" class="mb-4">
            {{ $page.props.errors.general }}
        </Alert>

        <!-- Filtros de búsqueda -->
        <form class="mb-4 flex flex-wrap items-end gap-3" role="search" @submit.prevent="goToPage(1)">
            <div class="w-64">
                <label for="filter-q" class="mb-1 block text-xs text-text-muted">Buscar rol</label>
                <Input id="filter-q" v-model="filters.q" placeholder="Nombre..." />
            </div>
            <div class="w-48">
                <label for="filter-status" class="mb-1 block text-xs text-text-muted">Estado</label>
                <Select id="filter-status" v-model="filters.status" :options="statusOptions" />
            </div>
            <IconButton icon="search" label="Buscar" type="submit" variant="primary" />
            <IconButton v-if="hasFilters" icon="clear" label="Limpiar filtros" @click="reset" />
        </form>

        <Table :columns="columns" :rows="roles">
            <template #cell-label="{ row }">
                <div class="flex items-center gap-2">
                    <span class="font-medium text-text">{{ row.label }}</span>
                    <Badge v-if="row.locked" variant="neutral">Sistema</Badge>
                </div>
            </template>

            <template #cell-description="{ row }">
                <span class="text-sm text-text-muted">
                    {{ row.description || '—' }}
                </span>
            </template>

            <template #cell-user_count="{ row }">
                <span class="text-sm font-medium">
                    {{ row.user_count }}
                </span>
            </template>

            <template #cell-status="{ row }">
                <Badge :variant="row.is_active ? 'success' : 'danger'">
                    {{ row.is_active ? 'Activo' : 'Inactivo' }}
                </Badge>
            </template>

            <template #cell-actions="{ row }">
                <div class="flex items-center gap-2">
                    <IconButton
                        icon="edit"
                        :label="row.locked ? 'Ver permisos (bloqueado)' : 'Configurar permisos'"
                        variant="primary"
                        @click="openPermissionsModal(row as unknown as RoleRow)"
                    />
                    <IconButton
                        v-if="!row.locked"
                        :icon="row.is_active ? 'deactivate' : 'activate'"
                        :label="row.is_active ? 'Inactivar rol' : 'Activar rol'"
                        :variant="row.is_active ? 'danger' : 'success'"
                        @click="toggleRoleStatus(row as unknown as RoleRow)"
                    />
                </div>
            </template>

            <template #empty>
                <EmptyState
                    :title="hasFilters ? 'Sin resultados para la búsqueda' : 'No hay roles registrados'"
                    :description="hasFilters ? 'Intenta ajustando los términos de búsqueda o estado.' : ''"
                />
            </template>
        </Table>

        <!-- Modal para editar permisos -->
        <Modal
            v-if="editingRole"
            :open="!!editingRole"
            :title="`Permisos de: ${editingRole.label}`"
            size="2xl"
            @close="editingRole = null"
        >
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <p v-if="editingRole.locked" class="rounded bg-background-muted p-2 text-xs text-text-muted">
                        Los permisos del Administrador están protegidos y no pueden modificarse.
                    </p>
                    <span class="text-xs text-text-muted">
                        {{ selectedPermissions.length }} permisos asignados
                    </span>
                </div>

                <div class="flex max-h-[28rem] flex-col gap-3 overflow-y-auto pr-1">
                    <div
                        v-for="(items, moduleName) in groupedPermissions"
                        :key="moduleName"
                        class="rounded-lg border border-border bg-card p-3 shadow-xs"
                    >
                        <div class="mb-2 flex items-center gap-2">
                            <Badge variant="neutral">{{ moduleName }}</Badge>
                            <span class="text-xs text-text-muted">
                                {{ items.length }} {{ items.length === 1 ? 'permiso' : 'permisos' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <div
                                v-for="p in items"
                                :key="p.name"
                                class="flex flex-col gap-0.5 rounded-md p-1.5 transition-colors hover:bg-background-muted/40"
                            >
                                <Checkbox
                                    :id="`modal-perm-${p.name}`"
                                    :label="p.label"
                                    :disabled="editingRole.locked"
                                    :model-value="selectedPermissions.includes(p.name)"
                                    @update:model-value="togglePermission(p.name, $event)"
                                />
                                <p
                                    v-if="p.description"
                                    class="pl-6 text-xs text-text-muted"
                                >
                                    {{ p.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <template #actions>
                <div class="flex justify-end gap-2">
                    <Button
                        variant="secondary"
                        :disabled="isSavingPermissions"
                        @click="editingRole = null"
                    >
                        Cancelar
                    </Button>
                    <Button
                        v-if="!editingRole.locked"
                        variant="primary"
                        :loading="isSavingPermissions"
                        loading-text="Guardando..."
                        @click="savePermissions"
                    >
                        Guardar y cerrar
                    </Button>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>
