<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import Card from '@/design-system/Card.vue';
import Checkbox from '@/design-system/Checkbox.vue';
import FormField from '@/design-system/FormField.vue';
import Input from '@/design-system/Input.vue';
import Textarea from '@/design-system/Textarea.vue';
import { notifyError } from '@/composables/useAlertDialog';
import type { PermissionItem } from '@/types';

const props = defineProps<{
    permissions: PermissionItem[];
}>();

const form = useForm({
    name: '',
    description: '',
    permissions: [] as string[],
});

// Agrupación de permisos por módulo
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

function togglePermission(permName: string, checked: boolean) {
    if (checked) {
        if (!form.permissions.includes(permName)) {
            form.permissions.push(permName);
        }
    } else {
        form.permissions = form.permissions.filter((p) => p !== permName);
    }
}

function submit() {
    form.post('/roles', {
        onError: (errors) => {
            if (errors.general) {
                void notifyError(errors.general);
            }
        },
    });
}
</script>

<template>
    <Head title="Nuevo rol" />
    <AppLayout>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Crear nuevo rol</h1>
            <Link href="/roles" class="text-sm text-primary hover:underline">
                &larr; Volver a roles
            </Link>
        </div>

        <Card class="max-w-3xl">
            <Alert v-if="$page.props.errors?.general" variant="danger" class="mb-4">
                {{ $page.props.errors.general }}
            </Alert>

            <form class="flex flex-col gap-5" @submit.prevent="submit">
                <FormField id="name" label="Nombre del rol" :error="form.errors.name">
                    <Input
                        id="name"
                        v-model="form.name"
                        placeholder="Ej. Coordinador Académico"
                        required
                    />
                </FormField>

                <FormField
                    id="description"
                    label="Descripción (opcional)"
                    :error="form.errors.description"
                >
                    <Textarea
                        id="description"
                        v-model="form.description"
                        placeholder="Describe el propósito o alcance de este rol..."
                        :rows="3"
                    />
                </FormField>

                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <label class="block text-sm font-medium text-text">
                            Permisos asignados
                        </label>
                        <span class="text-xs text-text-muted">
                            {{ form.permissions.length }} seleccionados
                        </span>
                    </div>

                    <div class="flex flex-col gap-4">
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

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div
                                    v-for="p in items"
                                    :key="p.name"
                                    class="flex flex-col gap-0.5 rounded-md p-1.5 transition-colors hover:bg-background-muted/40"
                                >
                                    <Checkbox
                                        :id="`perm-${p.name}`"
                                        :label="p.label"
                                        :model-value="form.permissions.includes(p.name)"
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

                    <p v-if="form.errors.permissions" class="mt-2 text-xs text-danger">
                        {{ form.errors.permissions }}
                    </p>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3">
                    <Link href="/roles">
                        <Button variant="secondary" type="button">Cancelar</Button>
                    </Link>
                    <Button variant="primary" type="submit" :loading="form.processing">
                        Crear rol
                    </Button>
                </div>
            </form>
        </Card>
    </AppLayout>
</template>
