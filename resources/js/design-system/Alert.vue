<script setup lang="ts">
withDefaults(defineProps<{ variant?: 'info' | 'success' | 'warning' | 'danger' }>(), { variant: 'info' });
</script>

<template>
    <div
        :role="variant === 'danger' ? 'alert' : 'status'"
        class="rounded-control border px-4 py-3 text-sm"
        :class="{
            'border-primary bg-primary-soft': variant === 'info',
            'border-success bg-success-soft': variant === 'success',
            'border-warning bg-warning-soft': variant === 'warning',
            'border-danger bg-danger-soft': variant === 'danger',
        }"
    >
        <slot />
    </div>
</template>
