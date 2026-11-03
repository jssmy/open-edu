<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';
import Button from '@/design-system/Button.vue';
import Input from '@/design-system/Input.vue';
import FormField from '@/design-system/FormField.vue';

const props = defineProps<{ token: string; email: string | null }>();
const form = useForm({
    token: props.token,
    email: props.email ?? '',
    password: '',
    password_confirmation: '',
});
</script>

<template>
    <Head title="Nueva contraseña" />
    <GuestLayout title="Nueva contraseña">
        <form class="flex flex-col gap-4" @submit.prevent="form.post('/reset-password')">
            <FormField id="email" label="Correo" :error="form.errors.email">
                <Input id="email" v-model="form.email" type="email" :error="form.errors.email" />
            </FormField>
            <FormField id="password" label="Nueva contraseña" :error="form.errors.password">
                <Input id="password" v-model="form.password" type="password" :error="form.errors.password" />
            </FormField>
            <FormField id="password_confirmation" label="Confirmar contraseña">
                <Input id="password_confirmation" v-model="form.password_confirmation" type="password" />
            </FormField>
            <Button type="submit" :loading="form.processing">Restablecer</Button>
        </form>
    </GuestLayout>
</template>
