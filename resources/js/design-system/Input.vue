<script setup lang="ts">
withDefaults(
    defineProps<{ id: string; type?: string; error?: string; disabled?: boolean; placeholder?: string }>(),
    { type: 'text', error: undefined, disabled: false, placeholder: undefined },
);
const model = defineModel<string | number | null>();
</script>

<template>
    <input
        :id="id"
        v-model="model"
        :type="type"
        :disabled="disabled"
        :placeholder="placeholder"
        :aria-invalid="!!error"
        :aria-describedby="error ? `${id}-error` : undefined"
        class="w-full rounded-control border bg-panel px-3 py-2 text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary disabled:bg-surface"
        :class="error ? 'border-danger' : 'border-border'"
    />
</template>
