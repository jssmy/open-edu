<script setup lang="ts">
withDefaults(
    defineProps<{
        variant?: 'info' | 'success' | 'warning' | 'danger';
        title?: string;
    }>(),
    { variant: 'info', title: undefined },
);
</script>

<template>
    <div
        :role="variant === 'danger' ? 'alert' : 'status'"
        class="relative flex gap-3 overflow-hidden rounded-card border p-4 shadow-card"
        :class="{
            'border-primary/20 bg-primary-soft': variant === 'info',
            'border-success/20 bg-success-soft': variant === 'success',
            'border-warning/20 bg-warning-soft': variant === 'warning',
            'border-danger/20 bg-danger-soft': variant === 'danger',
        }"
    >
        <span
            class="absolute inset-y-3 left-0 w-1 rounded-full"
            :class="{
                'bg-primary': variant === 'info',
                'bg-success': variant === 'success',
                'bg-warning': variant === 'warning',
                'bg-danger': variant === 'danger',
            }"
            aria-hidden="true"
        />
        <span
            class="ml-1 flex size-10 shrink-0 items-center justify-center rounded-full border border-current/10 bg-panel shadow-card"
            :class="{
                'text-primary': variant === 'info',
                'text-success': variant === 'success',
                'text-warning': variant === 'warning',
                'text-danger': variant === 'danger',
            }"
            aria-hidden="true"
        >
            <svg
                v-if="variant === 'info'"
                viewBox="0 0 24 24"
                class="size-5"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <circle cx="12" cy="12" r="9" />
                <path d="M12 11v5m0-8h.01" stroke-linecap="round" />
            </svg>
            <svg
                v-else-if="variant === 'success'"
                viewBox="0 0 24 24"
                class="size-5"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <circle cx="12" cy="12" r="9" />
                <path d="m8 12 2.5 2.5L16 9" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <svg
                v-else-if="variant === 'warning'"
                viewBox="0 0 24 24"
                class="size-5"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <path d="M10.3 4.7 3.8 16a2 2 0 0 0 1.7 3h13a2 2 0 0 0 1.7-3L13.7 4.7a2 2 0 0 0-3.4 0Z" />
                <path d="M12 9v4m0 3h.01" stroke-linecap="round" />
            </svg>
            <svg
                v-else
                viewBox="0 0 24 24"
                class="size-5"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
            >
                <circle cx="12" cy="12" r="9" />
                <path d="M12 8v5m0 3h.01" stroke-linecap="round" />
            </svg>
        </span>
        <div class="min-w-0 flex-1 self-center text-sm leading-6 text-text">
            <p v-if="title" class="mb-0.5 font-semibold">{{ title }}</p>
            <div class="text-text-muted"><slot /></div>
        </div>
    </div>
</template>
