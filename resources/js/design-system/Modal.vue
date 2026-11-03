<script setup lang="ts">
import { nextTick, onBeforeUnmount, useId, useTemplateRef, watch } from 'vue';

const props = defineProps<{ open: boolean; title: string }>();
const emit = defineEmits<{ close: [] }>();

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

    if (event.shiftKey && (document.activeElement === first || !dialog.value?.contains(document.activeElement))) {
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
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-text/50"
    >
        <div
            ref="dialog"
            role="dialog"
            aria-modal="true"
            :aria-labelledby="titleId"
            tabindex="-1"
            class="w-full max-w-md rounded-card bg-panel p-6 shadow-overlay focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            @keydown="handleKeydown"
        >
            <h2 :id="titleId" class="mb-4 text-lg font-semibold">{{ title }}</h2>
            <slot />
            <div class="mt-6 flex justify-end gap-2"><slot name="actions" /></div>
        </div>
    </div>
</template>
