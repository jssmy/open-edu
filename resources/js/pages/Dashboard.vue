<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/design-system/Card.vue';
</script>

<template>
    <Head title="Inicio" />
    <AppLayout>
        <Card><h1 class="text-xl font-semibold">Bienvenido a Open Edu</h1></Card>
    </AppLayout>
</template>
