<script setup lang="ts">
export interface SelectOption {
    value: string | number;
    label: string;
}
withDefaults(defineProps<{ id: string; options: SelectOption[]; error?: string; disabled?: boolean }>(), {
    error: undefined,
    disabled: false,
});
const model = defineModel<string | number | null>();
</script>

<template>
    <select
        :id="id"
        v-model="model"
        :disabled="disabled"
        :aria-invalid="!!error"
        :aria-describedby="error ? `${id}-error` : undefined"
        class="w-full rounded-control border bg-panel px-3 py-2 text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
        :class="error ? 'border-danger' : 'border-border'"
    >
        <option v-for="o in options" :key="o.value" :value="o.value">{{ o.label }}</option>
    </select>
</template>
