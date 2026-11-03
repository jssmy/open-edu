<script setup lang="ts">
import Button from '@/design-system/Button.vue';
import Modal from '@/design-system/Modal.vue';
import { useId } from 'vue';

withDefaults(
    defineProps<{
        open: boolean;
        variant?: 'info' | 'success' | 'warning' | 'danger';
        title: string;
        message: string;
        confirmLabel?: string;
        cancelLabel?: string;
    }>(),
    {
        variant: 'info',
        confirmLabel: 'Continuar',
        cancelLabel: 'Cancelar',
    },
);

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();
const messageId = useId();
</script>

<template>
    <Modal
        :open="open"
        :title="title"
        role="alertdialog"
        :description-id="messageId"
        @close="emit('cancel')"
    >
        <template #header="{ titleId }">
            <div class="mb-4 flex justify-center">
                <span
                    class="flex size-20 items-center justify-center rounded-full border-[3px] bg-panel"
                    :class="{
                        'border-primary/20 text-primary': variant === 'info',
                        'border-success/20 text-success': variant === 'success',
                        'border-warning/20 text-warning': variant === 'warning',
                        'border-danger/20 text-danger': variant === 'danger',
                    }"
                    aria-hidden="true"
                >
                    <svg
                        v-if="variant === 'info'"
                        viewBox="0 0 24 24"
                        class="size-10"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                    >
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 11v5m0-8h.01" stroke-linecap="round" />
                    </svg>
                    <svg
                        v-else-if="variant === 'success'"
                        viewBox="0 0 24 24"
                        class="size-10"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                    >
                        <circle cx="12" cy="12" r="9" />
                        <path d="m8 12 2.5 2.5L16 9" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <svg
                        v-else-if="variant === 'warning'"
                        viewBox="0 0 24 24"
                        class="size-10"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                    >
                        <path d="M10.3 4.7 3.8 16a2 2 0 0 0 1.7 3h13a2 2 0 0 0 1.7-3L13.7 4.7a2 2 0 0 0-3.4 0Z" />
                        <path d="M12 9v4m0 3h.01" stroke-linecap="round" />
                    </svg>
                    <svg
                        v-else
                        viewBox="0 0 24 24"
                        class="size-10"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.5"
                    >
                        <circle cx="12" cy="12" r="9" />
                        <path d="M12 8v5m0 3h.01" stroke-linecap="round" />
                    </svg>
                </span>
            </div>
            <h2 :id="titleId" class="text-center text-xl font-semibold tracking-tight text-text sm:text-2xl">
                {{ title }}
            </h2>
        </template>
        <p :id="messageId" class="mt-2 text-center text-sm leading-6 text-text-muted">
            {{ message }}
        </p>
        <template #actions>
            <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2">
                <Button variant="secondary" class="min-h-11 w-full" @click="emit('cancel')">
                    {{ cancelLabel }}
                </Button>
                <Button
                    :variant="variant === 'danger' ? 'danger' : 'primary'"
                    class="min-h-11 w-full"
                    @click="emit('confirm')"
                >
                    {{ confirmLabel }}
                </Button>
            </div>
        </template>
    </Modal>
</template>
