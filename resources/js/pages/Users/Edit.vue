<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Button from '@/design-system/Button.vue';
import Card from '@/design-system/Card.vue';
import FormField from '@/design-system/FormField.vue';
import Input from '@/design-system/Input.vue';
import Select from '@/design-system/Select.vue';

const props = defineProps<{
    user: { id: number; name: string; email: string; role: string | null };
    roles: { value: string; label: string }[];
}>();
const form = useForm({
    name: props.user.name,
    email: props.user.email,
    role: props.user.role ?? 'estudiante',
});
</script>

<template>
    <Head title="Editar usuario" />
    <AppLayout>
        <h1 class="mb-4 text-xl font-semibold">Editar usuario</h1>
        <Card class="max-w-lg">
            <Alert v-if="$page.props.errors?.general" variant="danger" class="mb-4">{{
                $page.props.errors.general
            }}</Alert>
            <form class="flex flex-col gap-4" @submit.prevent="form.put(`/users/${user.id}`)">
                <FormField id="name" label="Nombre" :error="form.errors.name">
                    <Input id="name" v-model="form.name" :error="form.errors.name" />
                </FormField>
                <FormField id="email" label="Correo" :error="form.errors.email">
                    <Input id="email" v-model="form.email" type="email" :error="form.errors.email" />
                </FormField>
                <FormField id="role" label="Rol" :error="form.errors.role">
                    <Select id="role" v-model="form.role" :options="roles" />
                </FormField>
                <div class="flex gap-2">
                    <Button type="submit" :loading="form.processing">Guardar</Button>
                    <Link href="/users" class="self-center text-sm text-text-muted">Cancelar</Link>
                </div>
            </form>
        </Card>
    </AppLayout>
</template>
