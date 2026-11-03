<script setup lang="ts">
defineProps<{ title: string; description?: string }>();
</script>

<template>
    <div
        class="flex flex-col items-center gap-2 rounded-card border border-dashed border-border p-10 text-center"
    >
        <p class="font-medium">{{ title }}</p>
        <p v-if="description" class="text-sm text-text-muted">{{ description }}</p>
        <slot />
    </div>
</template>
