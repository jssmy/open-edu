<script setup lang="ts">
withDefaults(defineProps<{ percentage: number; label?: string }>(), {
    label: 'Progreso del curso',
});
</script>

<template>
    <div
        class="h-3 overflow-hidden rounded-full bg-surface"
        role="progressbar"
        :aria-label="label"
        aria-valuemin="0"
        aria-valuemax="100"
        :aria-valuenow="percentage"
    >
        <div class="h-full rounded-full bg-primary transition-all" :style="{ width: `${percentage}%` }" />
    </div>
</template>
