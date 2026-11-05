<script setup lang="ts">
import { nextTick, onBeforeUnmount, useId, useTemplateRef, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        role?: 'dialog' | 'alertdialog';
        descriptionId?: string;
        size?: 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl';
    }>(),
    { role: 'dialog', descriptionId: undefined, size: 'md' },
);
const emit = defineEmits<{ close: [] }>();

const sizeClasses: Record<NonNullable<typeof props.size>, string> = {
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
    '4xl': 'max-w-4xl',
};

const dialog = useTemplateRef<HTMLDivElement>('dialog');
const titleId = useId();
const focusableSelector =
    'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';
let previouslyFocusedElement: HTMLElement | null = null;

watch(
    () => props.open,
    async (open) => {
        if (typeof document === 'undefined') {
            return;
        }

        if (open) {
            previouslyFocusedElement =
                document.activeElement instanceof HTMLElement ? document.activeElement : null;
            await nextTick();
            (dialog.value?.querySelector<HTMLElement>(focusableSelector) ?? dialog.value)?.focus();

            return;
        }

        await nextTick();
        if (previouslyFocusedElement?.isConnected) {
            previouslyFocusedElement.focus();
        }
        previouslyFocusedElement = null;
    },
    { immediate: true, flush: 'post' },
);

function handleKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        event.preventDefault();
        emit('close');

        return;
    }

    if (event.key !== 'Tab') {
        return;
    }

    const focusableElements = dialog.value?.querySelectorAll<HTMLElement>(focusableSelector);
    const first = focusableElements?.item(0);
    const last = focusableElements?.item((focusableElements.length ?? 1) - 1);

    if (!first || !last) {
        event.preventDefault();
        dialog.value?.focus();

        return;
    }

    if (
        event.shiftKey &&
        (document.activeElement === first || !dialog.value?.contains(document.activeElement))
    ) {
        event.preventDefault();
        last.focus();
    } else if (
        !event.shiftKey &&
        (document.activeElement === last || !dialog.value?.contains(document.activeElement))
    ) {
        event.preventDefault();
        first.focus();
    }
}

onBeforeUnmount(() => {
    if (previouslyFocusedElement?.isConnected) {
        previouslyFocusedElement.focus();
    }
});
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-text/50">
        <div
            ref="dialog"
            :role="role"
            aria-modal="true"
            :aria-labelledby="titleId"
            :aria-describedby="descriptionId"
            tabindex="-1"
            class="mx-4 max-h-[calc(100dvh-2rem)] w-full overflow-y-auto rounded-card border border-border bg-panel p-6 shadow-overlay focus:outline-none focus-visible:ring-2 focus-visible:ring-primary sm:mx-6 sm:p-7"
            :class="sizeClasses[size]"
            @keydown="handleKeydown"
        >
            <slot name="header" :title-id="titleId">
                <h2 :id="titleId" class="mb-4 text-lg font-semibold">{{ title }}</h2>
            </slot>
            <slot />
            <div class="mt-6">
                <slot name="footer">
                    <slot name="actions" />
                </slot>
            </div>
        </div>
    </div>
</template>
