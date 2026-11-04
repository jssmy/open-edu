<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Button from '@/design-system/Button.vue';
import Card from '@/design-system/Card.vue';
import Checkbox from '@/design-system/Checkbox.vue';
import FormField from '@/design-system/FormField.vue';
import Input from '@/design-system/Input.vue';
import Textarea from '@/design-system/Textarea.vue';

const props = defineProps<{
    permissions: string[];
}>();

const form = useForm({
    name: '',
    description: '',
    permissions: [] as string[],
});

function togglePermission(perm: string, checked: boolean) {
    if (checked) {
        if (!form.permissions.includes(perm)) {
            form.permissions.push(perm);
        }
    } else {
        form.permissions = form.permissions.filter((p) => p !== perm);
    }
}

function submit() {
    form.post('/roles');
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

        <Card class="max-w-2xl">
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
                    <label class="mb-2 block text-sm font-medium text-text">
                        Permisos asignados
                    </label>
                    <div class="grid grid-cols-1 gap-2 rounded-md border border-border p-3 sm:grid-cols-2">
                        <Checkbox
                            v-for="p in permissions"
                            :id="`perm-${p}`"
                            :key="p"
                            :label="p"
                            :model-value="form.permissions.includes(p)"
                            @update:model-value="togglePermission(p, $event)"
                        />
                    </div>
                    <p v-if="form.errors.permissions" class="mt-1 text-xs text-danger">
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
