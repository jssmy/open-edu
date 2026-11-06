<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';
import Button from '@/design-system/Button.vue';
import Input from '@/design-system/Input.vue';
import FormField from '@/design-system/FormField.vue';

const form = useForm({ login: '', email: '', password: '' });
</script>

<template>
    <Head title="Iniciar sesión" />
    <GuestLayout title="Open Edu">
        <form class="flex flex-col gap-4" @submit.prevent="form.post('/login')">
            <FormField id="login" label="Usuario o Correo" :error="form.errors.login || form.errors.email">
                <Input
                    id="login"
                    v-model="form.login"
                    type="text"
                    autocomplete="username"
                    placeholder="ej. juanperez o usuario@correo.com"
                    :error="form.errors.login || form.errors.email"
                />
            </FormField>
            <FormField id="password" label="Contraseña" :error="form.errors.password">
                <Input id="password" v-model="form.password" type="password" autocomplete="current-password" :error="form.errors.password" />
            </FormField>
            <Button type="submit" :loading="form.processing">Entrar</Button>
        </form>
    </GuestLayout>
</template>
