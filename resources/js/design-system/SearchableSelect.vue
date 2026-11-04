<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, toRef, useTemplateRef } from 'vue';
import type { SelectOption } from '@/design-system/Select.vue';
import { useSearchableSelect } from '@/composables/useSearchableSelect';

const props = withDefaults(
    defineProps<{
        id: string;
        options: SelectOption[];
        error?: string;
        disabled?: boolean;
        placeholder?: string;
        searchPlaceholder?: string;
        emptyMessage?: string;
        noResultsMessage?: string;
        maxVisible?: number;
    }>(),
    {
        error: undefined,
        disabled: false,
        placeholder: 'Seleccione una opción',
        searchPlaceholder: 'Buscar...',
        emptyMessage: 'No hay opciones disponibles.',
        noResultsMessage: 'No se encontraron resultados.',
        maxVisible: 5,
    },
);

const model = defineModel<string | number | null>();

const isOpen = ref(false);
const rootEl = useTemplateRef<HTMLDivElement>('rootEl');
const searchInputEl = useTemplateRef<HTMLInputElement>('searchInputEl');

const { query, filteredOptions, clearQuery } = useSearchableSelect(toRef(props, 'options'));

const selectedOption = computed(() =>
    props.options.find((option) => String(option.value) === String(model.value ?? '')),
);

async function toggleDropdown(): Promise<void> {
    if (props.disabled) {
        return;
    }

    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        clearQuery();
        await nextTick();
        searchInputEl.value?.focus();
    }
}

function closeDropdown(): void {
    isOpen.value = false;
    clearQuery();
}

function selectOption(option: SelectOption): void {
    model.value = option.value;
    closeDropdown();
}

function handleDocumentClick(event: MouseEvent): void {
    if (!isOpen.value || !rootEl.value) {
        return;
    }

    if (event.target instanceof Node && !rootEl.value.contains(event.target)) {
        closeDropdown();
    }
}

onMounted(() => {
    document.addEventListener('mousedown', handleDocumentClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleDocumentClick);
});
</script>

<template>
    <div ref="rootEl" class="relative w-full" @keydown.esc.stop="closeDropdown">
        <button
            :id="id"
            type="button"
            :disabled="disabled"
            :aria-expanded="isOpen"
            aria-haspopup="listbox"
            :aria-invalid="!!error"
            :aria-describedby="error ? `${id}-error` : undefined"
            class="flex min-h-11 w-full items-center justify-between gap-2 rounded-control border bg-panel px-3 py-2 text-left text-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:cursor-not-allowed disabled:bg-surface disabled:opacity-50"
            :class="error ? 'border-danger' : 'border-border'"
            data-testid="searchable-select-trigger"
            @click="toggleDropdown"
        >
            <span class="truncate" :class="selectedOption ? 'text-text' : 'text-text-muted'">
                {{ selectedOption ? selectedOption.label : placeholder }}
            </span>
            <svg
                aria-hidden="true"
                viewBox="0 0 20 20"
                class="size-4 shrink-0 text-text-muted"
                fill="currentColor"
            >
                <path
                    fill-rule="evenodd"
                    d="M5.2 7.5a.75.75 0 0 1 1.06 0L10 11.19l3.74-3.7a.75.75 0 1 1 1.06 1.07l-4.27 4.23a.75.75 0 0 1-1.06 0L5.2 8.56a.75.75 0 0 1 0-1.06Z"
                    clip-rule="evenodd"
                />
            </svg>
        </button>

        <div
            v-if="isOpen"
            class="absolute left-0 right-0 top-full z-50 mt-1 rounded-card border border-border bg-panel p-2 shadow-overlay"
            data-testid="searchable-select-popover"
        >
            <div class="mb-2 flex items-center gap-1">
                <input
                    :id="`${id}-search`"
                    ref="searchInputEl"
                    v-model="query"
                    type="text"
                    :placeholder="searchPlaceholder"
                    :aria-label="searchPlaceholder"
                    class="w-full rounded-control border border-border bg-surface px-3 py-2 text-sm text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    data-testid="searchable-select-input"
                />
                <button
                    v-if="query"
                    type="button"
                    aria-label="Limpiar búsqueda"
                    class="inline-flex size-9 shrink-0 items-center justify-center rounded-control text-text-muted hover:bg-surface hover:text-text"
                    @click="clearQuery"
                >
                    ✕
                </button>
            </div>

            <p
                v-if="options.length === 0"
                class="px-3 py-3 text-center text-xs text-text-muted"
                data-testid="searchable-select-empty"
            >
                {{ emptyMessage }}
            </p>
            <p
                v-else-if="filteredOptions.length === 0"
                class="px-3 py-3 text-center text-xs text-text-muted"
                data-testid="searchable-select-no-results"
            >
                {{ noResultsMessage }}
            </p>
            <ul
                v-else
                role="listbox"
                :aria-labelledby="id"
                class="max-h-[13.75rem] divide-y divide-border/50 overflow-y-auto"
                :style="{ maxHeight: `${props.maxVisible * 2.75}rem` }"
                data-testid="searchable-select-list"
            >
                <li v-for="option in filteredOptions" :key="option.value">
                    <button
                        type="button"
                        role="option"
                        :aria-selected="String(option.value) === String(model ?? '')"
                        class="flex min-h-11 w-full items-center justify-between gap-2 rounded-control px-3 py-2 text-left text-sm transition hover:bg-surface focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                        :class="
                            String(option.value) === String(model ?? '')
                                ? 'bg-primary-soft font-medium text-primary'
                                : 'text-text'
                        "
                        @click="selectOption(option)"
                    >
                        <span class="truncate">{{ option.label }}</span>
                        <span
                            v-if="String(option.value) === String(model ?? '')"
                            aria-hidden="true"
                            class="text-xs font-bold text-primary"
                        >
                            ✓
                        </span>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</template>

