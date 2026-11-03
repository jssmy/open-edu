<script setup lang="ts">
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import Card from '@/design-system/Card.vue';
import Checkbox from '@/design-system/Checkbox.vue';

interface RoleRow {
    id: number;
    name: string;
    label: string;
    locked: boolean;
    permissions: string[];
}

const props = defineProps<{ roles: RoleRow[]; permissions: string[] }>();
const selected = reactive<Record<number, string[]>>(
    Object.fromEntries(props.roles.map((r) => [r.id, [...r.permissions]])),
);

function toggle(roleId: number, permission: string, on: boolean) {
    const list = selected[roleId];
    selected[roleId] = on ? [...list, permission] : list.filter((p) => p !== permission);
}

function save(role: RoleRow) {
    router.put(`/roles/${role.id}/permissions`, { permissions: selected[role.id] }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Roles y permisos" />
    <AppLayout>
        <h1 class="mb-4 text-xl font-semibold">Roles y permisos</h1>
        <Alert v-if="$page.props.errors?.general" variant="danger" class="mb-4">{{
            $page.props.errors.general
        }}</Alert>
        <div class="grid gap-4 md:grid-cols-2">
            <Card v-for="role in roles" :key="role.id">
                <div class="mb-3 flex items-center gap-2">
                    <h2 class="font-medium">{{ role.label }}</h2>
                    <Badge v-if="role.locked">Bloqueado</Badge>
                </div>
                <div class="flex flex-col gap-2">
                    <Checkbox
                        v-for="p in permissions"
                        :id="`${role.id}-${p}`"
                        :key="p"
                        :label="p"
                        :disabled="role.locked"
                        :model-value="selected[role.id].includes(p)"
                        @update:model-value="toggle(role.id, p, $event)"
                    />
                </div>
                <Button v-if="!role.locked" class="mt-4" @click="save(role)">Guardar permisos</Button>
            </Card>
        </div>
    </AppLayout>
</template>
