<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

export type IconName = 'view' | 'edit' | 'activate' | 'deactivate' | 'search' | 'clear' | 'prev' | 'next' | 'users' | 'plus' | 'save' | 'delete' | 'archive';

const paths: Record<IconName, string> = {
    view: 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10-3a3 3 0 1 0 0 6 3 3 0 0 0 0-6Z',
    edit: 'M4 20h4L19 9l-4-4L4 16v4Zm9.5-13.5 4 4',
    activate: 'M5 12.5 10 17.5 19 7',
    deactivate: 'M12 3v9m5.7-6.3a8 8 0 1 1-11.4 0',
    search: 'm21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z',
    clear: 'm6 6 12 12M18 6 6 18',
    prev: 'm15 5-7 7 7 7',
    next: 'm9 5 7 7-7 7',
    users: 'M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20M9.5 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm11.5 9v-1.5a4 4 0 0 0-3-3.9M15 4.2a3.5 3.5 0 0 1 0 6.6',
    plus: 'M12 5v14M5 12h14',
    save: 'M5 4h11l3 3v13H5V4Zm3 0v5h7V4M8 20v-6h8v6',
    archive: 'M3 4h18v4H3V4Zm2 4v12h14V8M10 12h4',
    delete: 'M4 7h16M10 11v6m4-6v6M6 7l1 13h10l1-13M9 7V4h6v3',
};

withDefaults(
    defineProps<{
        icon: IconName;
        label: string;
        href?: string;
        variant?: 'neutral' | 'primary' | 'danger' | 'success';
        disabled?: boolean;
        showLabel?: boolean;
        text?: string;
        type?: 'button' | 'submit';
    }>(),
    { href: undefined, variant: 'neutral', disabled: false, showLabel: false, text: undefined, type: 'button' },
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
        class="inline-flex h-8 items-center justify-center gap-2 rounded-control border border-border bg-panel transition hover:bg-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:cursor-not-allowed disabled:opacity-50"
        :class="{
            'w-8': !showLabel,
            'px-3 text-sm font-medium': showLabel,
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
        <span v-if="showLabel">{{ text ?? label }}</span>
    </component>
</template>
