import { computed, ref, type Ref } from 'vue';
import type { SelectOption } from '@/design-system/Select.vue';

export function normalizeSearchText(value: string): string {
    return value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLocaleLowerCase()
        .trim();
}

export function useSearchableSelect(options: Ref<SelectOption[]>) {
    const query = ref('');

    const filteredOptions = computed(() => {
        const term = normalizeSearchText(query.value);
        if (!term) {
            return options.value;
        }

        return options.value.filter((option) =>
            normalizeSearchText(option.label).includes(term),
        );
    });

    function clearQuery(): void {
        query.value = '';
    }

    return {
        query,
        filteredOptions,
        clearQuery,
    };
}

