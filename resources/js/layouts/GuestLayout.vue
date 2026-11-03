<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/types';
import Alert from '@/design-system/Alert.vue';
import Card from '@/design-system/Card.vue';

defineProps<{ title: string }>();
const page = usePage<SharedProps>();
</script>

<template>
    <main class="flex min-h-screen items-center justify-center px-gutter">
        <div class="w-full max-w-sm">
            <h1 class="mb-4 text-center text-2xl font-semibold text-primary">{{ title }}</h1>
            <Alert v-if="page.props.flash?.success" variant="success" class="mb-4">{{
                page.props.flash.success
            }}</Alert>
            <Card><slot /></Card>
        </div>
    </main>
</template>
