<script setup lang="ts">
import { router, Link, useForm } from '@inertiajs/vue3';
import Button from '@/design-system/Button.vue';
import FormField from '@/design-system/FormField.vue';
import Input from '@/design-system/Input.vue';
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

function moveUnit(index: number, offset: number) {
    const unitIds = props.units.map((unit) => unit.id);
    const target = index + offset;
    if (target < 0 || target >= unitIds.length) {
        return;
    }

    [unitIds[index], unitIds[target]] = [unitIds[target], unitIds[index]];
    reorderUnits(unitIds);
}

function moveLesson(unit: Unit, index: number, offset: number) {
    const lessonIds = unit.lessons.map((lesson) => lesson.id);
    const target = index + offset;
    if (target < 0 || target >= lessonIds.length) {
        return;
    }

    [lessonIds[index], lessonIds[target]] = [lessonIds[target], lessonIds[index]];
    reorderLessons(lessonIds);
}

function deleteUnit(unitId: number) {
    if (globalThis.confirm('¿Eliminar esta unidad y sus lecciones?')) {
        router.delete(`/units/${unitId}`, { preserveScroll: true });
    }
}

function archive(lessonId: number) {
    archiveLesson(lessonId);
}

function remove(lessonId: number) {
    removeLesson(lessonId);
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
    <section>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold">Unidades y lecciones</h2>
            <form
                v-if="canManageContent"
                class="flex w-full flex-wrap items-end gap-2 sm:w-auto"
                @submit.prevent="createUnit"
            >
                <FormField
                    id="new-unit-title"
                    label="Nueva unidad"
                    :error="unitForm.errors.title"
                    class="min-w-0 flex-1"
                >
                    <Input id="new-unit-title" v-model="unitForm.title" :error="unitForm.errors.title" />
                </FormField>
                <Button type="submit" :loading="unitForm.processing">Agregar unidad</Button>
            </form>
        </div>

        <p
            v-if="units.length === 0"
            class="rounded-card border border-dashed border-border p-8 text-center text-text-muted"
        >
            Este curso aún no tiene unidades.
        </p>

        <ol v-else class="flex flex-col gap-4">
            <li
                v-for="(unit, unitIndex) in units"
                :key="unit.id"
                class="rounded-card border border-border bg-panel p-4"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="font-semibold">{{ unit.position }}. {{ unit.title }}</h3>
                    <template v-if="canManageContent">
                        <Button
                            variant="secondary"
                            :disabled="unitIndex === 0"
                            @click="moveUnit(unitIndex, -1)"
                        >
                            Subir
                        </Button>
                        <Button
                            variant="secondary"
                            :disabled="unitIndex === units.length - 1"
                            @click="moveUnit(unitIndex, 1)"
                        >
                            Bajar
                        </Button>
                        <Button variant="danger" @click="deleteUnit(unit.id)">Eliminar unidad</Button>
                    </template>
                </div>

                <ul class="mt-3 flex flex-col gap-2">
                    <li
                        v-for="(lesson, lessonIndex) in unit.lessons"
                        :key="lesson.id"
                        class="flex flex-wrap items-center justify-between gap-2 border-t border-border pt-2"
                    >
                        <div>
                            <p :class="{ 'text-text-muted line-through': lesson.is_archived }">
                                {{ lesson.position }}. {{ lesson.title }}
                            </p>
                            <p class="text-sm text-text-muted">{{ lessonContentLabel(lesson) }}</p>
                        </div>
                        <div v-if="canManageContent" class="flex flex-wrap gap-2">
                            <Link
                                :href="`/lessons/${lesson.id}/edit`"
                                class="inline-flex min-h-11 items-center rounded-control border border-border bg-panel px-3 text-sm font-medium text-text transition hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            >
                                Editar
                            </Link>
                            <Button
                                variant="secondary"
                                :disabled="lessonIndex === 0"
                                @click="moveLesson(unit, lessonIndex, -1)"
                            >
                                Subir
                            </Button>
                            <Button
                                variant="secondary"
                                :disabled="lessonIndex === unit.lessons.length - 1"
                                @click="moveLesson(unit, lessonIndex, 1)"
                            >
                                Bajar
                            </Button>
                            <Button
                                v-if="!lesson.is_archived"
                                variant="secondary"
                                @click="archive(lesson.id)"
                            >
                                Archivar
                            </Button>
                            <Button variant="danger" @click="remove(lesson.id)">Eliminar</Button>
                        </div>
                    </li>
                </ul>

                <div v-if="canManageContent" class="mt-3">
                    <Link
                        :href="`/units/${unit.id}/lessons/create`"
                        class="inline-flex min-h-11 items-center rounded-control border border-border bg-panel px-4 text-sm font-medium text-text transition hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    >
                        Agregar lección
                    </Link>
                </div>
            </li>
        </ol>
    </section>
</template>
