<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/layouts/GuestLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Button from '@/design-system/Button.vue';
import Input from '@/design-system/Input.vue';
import FormField from '@/design-system/FormField.vue';

const form = useForm({ current_password: '', password: '', password_confirmation: '' });
</script>

<template>
    <Head title="Cambiar contraseña" />
    <GuestLayout title="Cambiar contraseña">
        <Alert variant="warning" class="mb-4">Debes cambiar tu contraseña temporal antes de continuar.</Alert>
        <form class="flex flex-col gap-4" @submit.prevent="form.post('/password/change')">
            <FormField id="current_password" label="Contraseña actual" :error="form.errors.current_password">
                <Input
                    id="current_password"
                    v-model="form.current_password"
                    type="password"
                    :error="form.errors.current_password"
                />
            </FormField>
            <FormField id="password" label="Nueva contraseña" :error="form.errors.password">
                <Input id="password" v-model="form.password" type="password" :error="form.errors.password" />
            </FormField>
            <FormField id="password_confirmation" label="Confirmar contraseña">
                <Input id="password_confirmation" v-model="form.password_confirmation" type="password" />
            </FormField>
            <Button type="submit" :loading="form.processing">Guardar</Button>
        </form>
    </GuestLayout>
</template>
