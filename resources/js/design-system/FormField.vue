<script setup lang="ts">
defineProps<{ id: string; label: string; error?: string }>();
</script>

<template>
    <div class="flex flex-col gap-1">
        <label :for="id" class="text-sm font-medium">{{ label }}</label>
        <slot />
        <p v-if="error" :id="`${id}-error`" class="text-sm text-danger" role="alert">{{ error }}</p>
    </div>
</template>
