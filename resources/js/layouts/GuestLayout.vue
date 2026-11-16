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
            <div class="mb-4 flex justify-center">
                <div class="size-14 overflow-hidden rounded-xl bg-white p-1 ring-1 ring-border shadow-xs flex items-center justify-center">
                    <img
                        src="/images/workshops/logo.jpeg"
                        alt="Logo Asociación Un Día de Esperanza"
                        class="size-full object-contain"
                    />
                </div>
            </div>
            <h1 class="mb-4 text-center text-2xl font-semibold text-primary">{{ title }}</h1>
            <Card><slot /></Card>
        </div>
    </main>
</template>
