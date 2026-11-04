<script setup lang="ts">
import IconButton from '@/design-system/IconButton.vue';

defineProps<{ page: number; lastPage: number; total?: number; from?: number; to?: number }>();
defineEmits<{ change: [page: number] }>();
</script>

<template>
    <nav aria-label="Paginación" class="flex items-center justify-between gap-3 text-sm">
        <span v-if="total !== undefined" class="text-text-muted">
            {{ total === 0 ? '0 resultados' : `${from}–${to} de ${total}` }}
        </span>
        <span v-else />
        <div class="flex items-center gap-2">
            <IconButton
                icon="prev"
                label="Anterior"
                :disabled="page <= 1"
                @click="$emit('change', page - 1)"
            />
            <span>Página {{ page }} de {{ lastPage }}</span>
            <IconButton
                icon="next"
                label="Siguiente"
                :disabled="page >= lastPage"
                @click="$emit('change', page + 1)"
            />
        </div>
    </nav>
</template>
