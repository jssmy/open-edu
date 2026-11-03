<script setup lang="ts">
defineProps<{ open: boolean; title: string }>();
defineEmits<{ close: [] }>();
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-text/50"
        @keydown.esc="$emit('close')"
    >
        <div
            role="dialog"
            aria-modal="true"
            :aria-label="title"
            class="w-full max-w-md rounded-card bg-panel p-6 shadow-overlay"
        >
            <h2 class="mb-4 text-lg font-semibold">{{ title }}</h2>
            <slot />
            <div class="mt-6 flex justify-end gap-2"><slot name="actions" /></div>
        </div>
    </div>
</template>
