<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';
import Button from '@/design-system/Button.vue';
import Input from '@/design-system/Input.vue';
import FormField from '@/design-system/FormField.vue';

const form = useForm({ email: '', password: '' });
</script>

<template>
    <Head title="Iniciar sesión" />
    <GuestLayout title="Open Edu">
        <form class="flex flex-col gap-4" @submit.prevent="form.post('/login')">
            <FormField id="email" label="Correo" :error="form.errors.email">
                <Input id="email" v-model="form.email" type="email" :error="form.errors.email" />
            </FormField>
            <FormField id="password" label="Contraseña" :error="form.errors.password">
                <Input id="password" v-model="form.password" type="password" :error="form.errors.password" />
            </FormField>
            <Button type="submit" :loading="form.processing">Entrar</Button>
            <Link href="/forgot-password" class="text-center text-sm text-primary"
                >¿Olvidaste tu contraseña?</Link
            >
        </form>
    </GuestLayout>
</template>
