<script setup lang="ts">
withDefaults(defineProps<{ variant?: 'neutral' | 'success' | 'warning' | 'danger' }>(), {
    variant: 'neutral',
});
</script>

<template>
    <span
        class="inline-block rounded-full px-2 py-0.5 text-xs font-medium"
        :class="{
            'bg-surface text-text-muted': variant === 'neutral',
            'bg-success-soft text-success': variant === 'success',
            'bg-warning-soft text-warning': variant === 'warning',
            'bg-danger-soft text-danger': variant === 'danger',
        }"
    >
        <slot />
    </span>
</template>
