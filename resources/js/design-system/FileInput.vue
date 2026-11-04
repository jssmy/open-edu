<script setup lang="ts">
import { computed, ref } from 'vue';

const props = withDefaults(
    defineProps<{
        id: string;
        accept?: string;
        required?: boolean;
        error?: string;
        buttonLabel?: string;
        hint?: string;
        currentFileName?: string;
    }>(),
    {
        accept: undefined,
        required: false,
        error: undefined,
        buttonLabel: 'Seleccionar archivo',
        hint: undefined,
        currentFileName: undefined,
    },
);
const model = defineModel<globalThis.File | null>({ default: null });
const input = ref<globalThis.HTMLInputElement | null>(null);
const isDragging = ref(false);

const fileName = computed(() => model.value?.name ?? props.currentFileName ?? null);
const sizeLabel = computed(() => {
    const bytes = model.value?.size;

    if (bytes === undefined) {
        return '';
    }

    return bytes >= 1024 * 1024
        ? `${(bytes / 1024 / 1024).toFixed(1)} MB`
        : `${Math.max(1, Math.round(bytes / 1024))} KB`;
});

function onChange(event: globalThis.Event): void {
    model.value = (event.target as globalThis.HTMLInputElement).files?.[0] ?? null;
}

function onDrop(event: globalThis.DragEvent): void {
    isDragging.value = false;
    const file = event.dataTransfer?.files?.[0];

    if (file) {
        model.value = file;
    }
}

function clear(): void {
    model.value = null;
    if (input.value) {
        input.value.value = '';
    }
}
</script>

<template>
    <div
        class="flex flex-wrap items-center gap-3 rounded-card border-2 border-dashed p-3 transition"
        :class="[
            error ? 'border-danger' : isDragging ? 'border-primary bg-primary/5' : 'border-border bg-surface',
        ]"
        @dragover.prevent="isDragging = true"
        @dragleave="isDragging = false"
        @drop.prevent="onDrop"
    >
        <input
            :id="id"
            ref="input"
            type="file"
            class="peer sr-only"
            :accept="accept"
            :required="required && !fileName"
            :aria-invalid="!!error"
            :aria-describedby="error ? `${id}-error` : undefined"
            @change="onChange"
        />
        <label
            :for="id"
            class="inline-flex min-h-11 cursor-pointer items-center gap-2 rounded-control border border-border bg-panel px-4 text-sm font-medium text-text transition hover:bg-surface peer-focus-visible:ring-2 peer-focus-visible:ring-primary"
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
                <path d="M12 16V4m0 0 4 4m-4-4L8 8M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3" />
            </svg>
            {{ fileName ? 'Cambiar archivo' : buttonLabel }}
        </label>
        <div class="min-w-0 flex-1 text-sm">
            <p v-if="fileName" class="flex items-center gap-2 truncate" data-testid="file-name">
                <svg
                    aria-hidden="true"
                    viewBox="0 0 24 24"
                    class="size-4 shrink-0 text-text-muted"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M14 3H7a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V7l-4-4Zm0 0v4h4" />
                </svg>
                <span class="truncate font-medium">{{ fileName }}</span>
                <span v-if="sizeLabel" class="shrink-0 text-text-muted">{{ sizeLabel }}</span>
            </p>
            <p v-else class="text-text-muted">
                Ningún archivo seleccionado. También puedes arrastrarlo aquí.
            </p>
            <p v-if="hint" class="text-xs text-text-muted">{{ hint }}</p>
        </div>
        <button
            v-if="model"
            type="button"
            class="inline-flex size-8 items-center justify-center rounded-control border border-border bg-panel text-text-muted hover:bg-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            aria-label="Quitar archivo seleccionado"
            title="Quitar archivo seleccionado"
            @click="clear"
        >
            <svg
                aria-hidden="true"
                viewBox="0 0 24 24"
                class="size-4"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
            >
                <path d="m6 6 12 12M18 6 6 18" />
            </svg>
        </button>
    </div>
</template>
