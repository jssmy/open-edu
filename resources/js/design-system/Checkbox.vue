<script setup lang="ts">
defineProps<{ id: string; label: string; disabled?: boolean }>();
const model = defineModel<boolean>({ default: false });
</script>

<template>
    <label :for="id" class="inline-flex items-center gap-2 text-sm">
        <input
            :id="id"
            v-model="model"
            type="checkbox"
            :disabled="disabled"
            class="h-4 w-4 rounded border-border"
        />
        {{ label }}
    </label>
</template>
