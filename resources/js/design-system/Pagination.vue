<script setup lang="ts">
defineProps<{ page: number; lastPage: number }>();
defineEmits<{ change: [page: number] }>();
</script>

<template>
    <nav aria-label="Paginación" class="flex items-center justify-between text-sm">
        <button
            type="button"
            class="rounded-control border border-border px-3 py-1 disabled:opacity-50"
            :disabled="page <= 1"
            @click="$emit('change', page - 1)"
        >
            Anterior
        </button>
        <span>Página {{ page }} de {{ lastPage }}</span>
        <button
            type="button"
            class="rounded-control border border-border px-3 py-1 disabled:opacity-50"
            :disabled="page >= lastPage"
            @click="$emit('change', page + 1)"
        >
            Siguiente
        </button>
    </nav>
</template>
