<script setup lang="ts">
import { toRef } from 'vue';
import Checkbox from '@/design-system/Checkbox.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import IconButton from '@/design-system/IconButton.vue';
import Input from '@/design-system/Input.vue';
import { useTeacherPicker, type TeacherOption } from '@/composables/useTeacherPicker';

const props = defineProps<{
    teachers: TeacherOption[];
    assignedIds: number[];
}>();

const selectedIds = defineModel<number[]>({ required: true });

const { query, visibleTeachers, hasTeachers, isSearching } = useTeacherPicker(
    toRef(props, 'teachers'),
    toRef(props, 'assignedIds'),
);

function isSelected(id: number): boolean {
    return selectedIds.value.includes(id);
}

function toggleTeacher(id: number, checked: boolean) {
    selectedIds.value = checked
        ? [...selectedIds.value, id]
        : selectedIds.value.filter((selectedId) => selectedId !== id);
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <p v-if="!hasTeachers" class="text-sm text-text-muted">No hay docentes disponibles.</p>
        <template v-else>
            <div class="flex items-end gap-2">
                <label class="flex min-w-0 flex-1 flex-col gap-1 text-sm font-medium" for="teacher-search">
                    Buscar docente
                    <Input id="teacher-search" v-model="query" placeholder="Nombre del docente" />
                </label>
                <IconButton v-if="query !== ''" icon="clear" label="Limpiar búsqueda" @click="query = ''" />
            </div>
            <ul
                v-if="visibleTeachers.length > 0"
                class="flex max-h-[30rem] flex-col gap-2 overflow-y-auto pr-1"
                aria-label="Lista de docentes"
                data-testid="teacher-list"
            >
                <li
                    v-for="teacher in visibleTeachers"
                    :key="teacher.id"
                    class="flex min-h-11 items-center rounded-control border border-border px-3 hover:bg-surface"
                >
                    <Checkbox
                        :id="`teacher-${teacher.id}`"
                        :label="teacher.name"
                        :model-value="isSelected(teacher.id)"
                        @update:model-value="toggleTeacher(teacher.id, $event)"
                    />
                </li>
            </ul>
            <EmptyState v-else-if="isSearching" title="No se encontraron docentes" />
        </template>
    </div>
</template>
