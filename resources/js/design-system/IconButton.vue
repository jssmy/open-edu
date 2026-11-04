<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

export type IconName = 'view' | 'edit' | 'activate' | 'deactivate' | 'search' | 'clear' | 'prev' | 'next';

const paths: Record<IconName, string> = {
    view: 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10-3a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z',
    edit: 'M4 20h4L19 9l-4-4L4 16v4Zm9.5-13.5 4 4',
    activate: 'M5 12.5 10 17.5 19 7',
    deactivate: 'M12 3v9m5.7-6.3a8 8 0 1 1-11.4 0',
    search: 'm21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z',
    clear: 'm6 6 12 12M18 6 6 18',
    prev: 'm15 5-7 7 7 7',
    next: 'm9 5 7 7-7 7',
};

withDefaults(
    defineProps<{
        icon: IconName;
        label: string;
        href?: string;
        variant?: 'neutral' | 'primary' | 'danger' | 'success';
        disabled?: boolean;
        type?: 'button' | 'submit';
    }>(),
    { href: undefined, variant: 'neutral', disabled: false, type: 'button' },
);
</script>

<template>
    <component
        :is="href ? Link : 'button'"
        :href="href"
        :type="href ? undefined : type"
        :disabled="href ? undefined : disabled"
        :aria-label="label"
        :title="label"
        class="inline-flex size-8 items-center justify-center rounded-control border border-border bg-panel transition hover:bg-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:cursor-not-allowed disabled:opacity-50"
        :class="{
            'text-text-muted': variant === 'neutral',
            'text-primary': variant === 'primary',
            'text-danger': variant === 'danger',
            'text-success': variant === 'success',
        }"
    >
        <svg
            aria-hidden="true"
            viewBox="0 0 24 24"
            class="size-4"
            fill="none"
            stroke="currentColor"
            stroke-width="1.8"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path :d="paths[icon]" />
        </svg>
    </component>
</template>
