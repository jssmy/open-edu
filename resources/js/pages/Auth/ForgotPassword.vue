<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';
import Button from '@/design-system/Button.vue';
import Input from '@/design-system/Input.vue';
import FormField from '@/design-system/FormField.vue';

const form = useForm({ email: '' });
</script>

<template>
    <Head title="Recuperar contraseña" />
    <GuestLayout title="Recuperar contraseña">
        <form class="flex flex-col gap-4" @submit.prevent="form.post('/forgot-password')">
            <FormField id="email" label="Correo" :error="form.errors.email">
                <Input id="email" v-model="form.email" type="email" :error="form.errors.email" />
            </FormField>
            <Button type="submit" :loading="form.processing">Enviar enlace</Button>
            <Link href="/login" class="text-center text-sm text-primary">Volver a iniciar sesión</Link>
        </form>
    </GuestLayout>
</template>
