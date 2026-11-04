<script setup lang="ts">
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Draggable from 'vuedraggable';
import IconButton from '@/design-system/IconButton.vue';
import FormField from '@/design-system/FormField.vue';
import Input from '@/design-system/Input.vue';
import { requestAlertDialog } from '@/composables/useAlertDialog';
import { archiveLesson, removeLesson, reorderLessons, reorderUnits } from '@/services/courses';
import type { RichTextDocument } from '@/types/richText';

interface Lesson {
    id: number;
    title: string;
    position: number;
    content_type: 'text' | 'document' | 'image' | 'link' | 'video';
    content: RichTextDocument | string | null;
    is_archived: boolean;
}

interface Unit {
    id: number;
    title: string;
    position: number;
    lessons: Lesson[];
}

const props = defineProps<{ courseId: number; units: Unit[]; canManageContent: boolean }>();
const unitForm = useForm({ title: '', position: 1 });

function createUnit() {
    unitForm.position = props.units.length + 1;
    unitForm.post(`/courses/${props.courseId}/units`, {
        preserveScroll: true,
        onSuccess: () => unitForm.reset(),
    });
}

function cloneUnits(units: Unit[]): Unit[] {
    return units.map((unit) => ({ ...unit, lessons: [...unit.lessons] }));
}

const orderedUnits = ref<Unit[]>(cloneUnits(props.units));

watch(
    () => props.units,
    (units) => {
        orderedUnits.value = cloneUnits(units);
    },
);

function onUnitsReordered(units: Unit[]) {
    orderedUnits.value = units;
    reorderUnits(units.map((unit) => unit.id));
}

function onLessonsReordered(unit: Unit, lessons: Lesson[]) {
    unit.lessons = lessons;
    reorderLessons(lessons.map((lesson) => lesson.id));
}

async function deleteUnit(unitId: number) {
    if (
        await requestAlertDialog({
            variant: 'danger',
            title: '¿Eliminar esta unidad?',
            message: 'También se eliminarán sus lecciones. Esta acción no se puede deshacer.',
            confirmLabel: 'Eliminar unidad',
        })
    ) {
        router.delete(`/units/${unitId}`, { preserveScroll: true });
    }
}

function archive(lessonId: number) {
    archiveLesson(lessonId);
}

async function remove(lessonId: number) {
    if (
        await requestAlertDialog({
            variant: 'danger',
            title: '¿Eliminar esta lección?',
            message: 'Esta acción no se puede deshacer.',
            confirmLabel: 'Eliminar lección',
        })
    ) {
        removeLesson(lessonId);
    }
}

function lessonContentLabel(lesson: Lesson): string {
    if (lesson.content_type === 'text') {
        return documentText(lesson.content);
    }

    if (lesson.content_type === 'link' || lesson.content_type === 'video') {
        return typeof lesson.content === 'string' ? lesson.content : '';
    }

    return typeof lesson.content === 'string'
        ? (lesson.content.split('/').at(-1) ?? 'Archivo adjunto')
        : 'Archivo adjunto';
}

function documentText(content: Lesson['content']): string {
    if (content === null || typeof content === 'string') {
        return content ?? '';
    }

    return content.content
        .flatMap((node) =>
            node.type === 'text' ? [node.text ?? ''] : (node.content?.map((child) => child.text ?? '') ?? []),
        )
        .join('');
}
</script>

<template>
    <section class="rounded-card border border-border bg-panel shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-border p-5">
            <div>
                <h2 class="text-lg font-semibold">Unidades y lecciones</h2>
                <p v-if="canManageContent && orderedUnits.length > 1" class="text-sm text-text-muted">
                    Arrastra el asa para reordenar unidades y lecciones.
                </p>
            </div>
        </div>

        <form
            v-if="canManageContent"
            class="border-b border-border bg-surface/60 p-5"
            novalidate
            @submit.prevent="createUnit"
        >
            <FormField
                id="new-unit-title"
                label="Nueva unidad"
                :error="unitForm.errors.title"
                class="min-w-0"
            >
                <div class="flex flex-col gap-2 sm:flex-row">
                    <Input
                        id="new-unit-title"
                        v-model="unitForm.title"
                        class="sm:flex-1"
                        placeholder="Ej. Unidad 1: Introducción al curso"
                        maxlength="255"
                        :error="unitForm.errors.title"
                    />
                    <IconButton
                        type="submit"
                        icon="plus"
                        label="Agregar"
                        show-label
                        variant="primary"
                        class="shrink-0 self-end"
                        :disabled="unitForm.processing || unitForm.title.trim() === ''"
                    />
                </div>
            </FormField>
            <p class="mt-2 text-xs text-text-muted">
                Usa un nombre descriptivo. Presiona Enter para agregarla.
            </p>
        </form>

        <p
            v-if="orderedUnits.length === 0"
            class="m-5 rounded-card border border-dashed border-border p-8 text-center text-text-muted"
        >
            Este curso aún no tiene unidades.
        </p>

        <Draggable
            v-else
            :model-value="orderedUnits"
            item-key="id"
            tag="ol"
            handle=".unit-handle"
            :disabled="!canManageContent"
            ghost-class="opacity-40"
            :animation="180"
            class="flex flex-col gap-4 p-5"
            @update:model-value="onUnitsReordered"
        >
            <template #item="{ element: unit, index: unitIndex }">
                <li class="overflow-hidden rounded-card border border-border bg-surface">
                    <div class="flex items-center gap-3 border-b border-border bg-panel px-4 py-3">
                        <button
                            v-if="canManageContent"
                            type="button"
                            class="unit-handle flex size-9 shrink-0 cursor-grab touch-none items-center justify-center rounded-control text-text-muted hover:bg-surface active:cursor-grabbing focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            :aria-label="`Arrastrar unidad ${unit.title}`"
                        >
                            <svg viewBox="0 0 20 20" class="size-5" fill="currentColor" aria-hidden="true">
                                <circle cx="7" cy="5" r="1.5" />
                                <circle cx="13" cy="5" r="1.5" />
                                <circle cx="7" cy="10" r="1.5" />
                                <circle cx="13" cy="10" r="1.5" />
                                <circle cx="7" cy="15" r="1.5" />
                                <circle cx="13" cy="15" r="1.5" />
                            </svg>
                        </button>
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-primary-soft text-sm font-semibold text-primary"
                        >
                            {{ unitIndex + 1 }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate font-semibold">{{ unit.title }}</h3>
                            <p class="text-xs text-text-muted">{{ unit.lessons.length }} lecciones</p>
                        </div>
                        <IconButton
                            v-if="canManageContent"
                            icon="delete"
                            :label="`Eliminar unidad ${unit.title}`"
                            text="Eliminar unidad"
                            show-label
                            variant="danger"
                            @click="deleteUnit(unit.id)"
                        />
                    </div>

                    <Draggable
                        :model-value="unit.lessons"
                        item-key="id"
                        tag="ul"
                        :group="`lessons-of-unit-${unit.id}`"
                        handle=".lesson-handle"
                        :disabled="!canManageContent"
                        ghost-class="opacity-40"
                        :animation="180"
                        class="flex flex-col divide-y divide-border"
                        @update:model-value="(lessons: Lesson[]) => onLessonsReordered(unit, lessons)"
                    >
                        <template #item="{ element: lesson, index: lessonIndex }">
                            <li class="flex flex-wrap items-center gap-3 bg-panel px-4 py-3">
                                <button
                                    v-if="canManageContent"
                                    type="button"
                                    class="lesson-handle flex size-8 shrink-0 cursor-grab touch-none items-center justify-center rounded-control text-text-muted hover:bg-surface active:cursor-grabbing focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                                    :aria-label="`Arrastrar lección ${lesson.title}`"
                                >
                                    <svg
                                        viewBox="0 0 20 20"
                                        class="size-4"
                                        fill="currentColor"
                                        aria-hidden="true"
                                    >
                                        <circle cx="7" cy="5" r="1.5" />
                                        <circle cx="13" cy="5" r="1.5" />
                                        <circle cx="7" cy="10" r="1.5" />
                                        <circle cx="13" cy="10" r="1.5" />
                                        <circle cx="7" cy="15" r="1.5" />
                                        <circle cx="13" cy="15" r="1.5" />
                                    </svg>
                                </button>
                                <div class="min-w-0 flex-1 basis-48">
                                    <p
                                        class="truncate text-sm font-medium"
                                        :class="{ 'text-text-muted line-through': lesson.is_archived }"
                                    >
                                        {{ lessonIndex + 1 }}. {{ lesson.title }}
                                    </p>
                                    <p class="truncate text-xs text-text-muted">
                                        {{ lessonContentLabel(lesson) }}
                                    </p>
                                </div>
                                <div v-if="canManageContent" class="flex flex-wrap gap-2">
                                    <IconButton
                                        icon="edit"
                                        :label="`Editar lección ${lesson.title}`"
                                        :href="`/lessons/${lesson.id}/edit`"
                                        variant="primary"
                                    />
                                    <IconButton
                                        v-if="!lesson.is_archived"
                                        icon="archive"
                                        :label="`Archivar lección ${lesson.title}`"
                                        @click="archive(lesson.id)"
                                    />
                                    <IconButton
                                        icon="delete"
                                        :label="`Eliminar lección ${lesson.title}`"
                                        variant="danger"
                                        @click="remove(lesson.id)"
                                    />
                                </div>
                            </li>
                        </template>
                    </Draggable>
                    <p v-if="unit.lessons.length === 0" class="bg-panel px-4 py-3 text-sm text-text-muted">
                        Sin lecciones todavía.
                    </p>

                    <div v-if="canManageContent" class="bg-panel px-4 pb-4 pt-2">
                        <IconButton
                            icon="plus"
                            label="Agregar lección"
                            show-label
                            :href="`/units/${unit.id}/lessons/create`"
                            variant="primary"
                        />
                    </div>
                </li>
            </template>
        </Draggable>
    </section>
</template>
