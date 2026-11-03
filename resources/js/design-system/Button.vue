<script setup lang="ts">
withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'danger';
        type?: 'button' | 'submit';
        loading?: boolean;
        disabled?: boolean;
    }>(),
    { variant: 'primary', type: 'button', loading: false, disabled: false },
);
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :aria-busy="loading"
        class="inline-flex items-center justify-center gap-2 rounded-control px-4 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:cursor-not-allowed disabled:opacity-50"
        :class="{
            'bg-primary text-white hover:bg-primary-hover': variant === 'primary',
            'border border-border bg-panel text-text hover:bg-surface': variant === 'secondary',
            'bg-danger text-white hover:bg-danger-hover': variant === 'danger',
        }"
    >
        <slot />
    </button>
</template>
