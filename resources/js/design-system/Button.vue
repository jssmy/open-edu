<script setup lang="ts">
withDefaults(
    defineProps<{
        variant?: 'primary' | 'secondary' | 'danger';
        type?: 'button' | 'submit';
        loading?: boolean;
        loadingText?: string;
        disabled?: boolean;
    }>(),
    { variant: 'primary', type: 'button', loading: false, loadingText: undefined, disabled: false },
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
        <svg
            v-if="loading"
            class="size-4 animate-spin shrink-0"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            aria-hidden="true"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
        </svg>
        <span v-if="loading && loadingText">{{ loadingText }}</span>
        <slot v-else />
    </button>
</template>
