<script setup lang="ts">
withDefaults(defineProps<{ id: string; rows?: number; error?: string; disabled?: boolean }>(), {
    rows: 4,
    error: undefined,
    disabled: false,
});
const model = defineModel<string | null>();
</script>

<template>
    <textarea
        :id="id"
        v-model="model"
        :rows="rows"
        :disabled="disabled"
        :aria-invalid="!!error"
        class="w-full rounded-control border bg-panel px-3 py-2 text-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
        :class="error ? 'border-danger' : 'border-border'"
    />
</template>
