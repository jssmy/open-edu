<script setup lang="ts">
export interface Column {
    key: string;
    label: string;
}
defineProps<{ columns: Column[]; rows: Record<string, unknown>[]; loading?: boolean }>();
</script>

<template>
    <div class="overflow-x-auto rounded-card border border-border bg-panel">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface text-text-muted">
                <tr>
                    <th v-for="c in columns" :key="c.key" scope="col" class="px-4 py-2 font-medium">
                        {{ c.label }}
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="loading">
                    <td :colspan="columns.length" class="px-4 py-6 text-center">Cargando…</td>
                </tr>
                <tr v-for="(row, i) in rows" v-else :key="i" class="border-t border-border">
                    <td v-for="c in columns" :key="c.key" class="px-4 py-2">
                        <slot :name="`cell-${c.key}`" :row="row">{{ row[c.key] }}</slot>
                    </td>
                </tr>
            </tbody>
        </table>
        <slot v-if="!loading && rows.length === 0" name="empty" />
    </div>
</template>
