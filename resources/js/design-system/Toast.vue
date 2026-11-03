<script setup lang="ts">
withDefaults(defineProps<{ message: string; variant?: 'success' | 'danger' }>(), { variant: 'success' });
</script>

<template>
    <div
        role="status"
        class="fixed bottom-4 right-4 rounded-control px-4 py-3 text-sm text-white shadow-overlay"
        :class="variant === 'success' ? 'bg-success' : 'bg-danger'"
    >
        {{ message }}
    </div>
</template>
