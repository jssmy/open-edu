<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import Button from '@/design-system/Button.vue';
import FormField from '@/design-system/FormField.vue';
import Input from '@/design-system/Input.vue';
import Select from '@/design-system/Select.vue';
import Textarea from '@/design-system/Textarea.vue';
import { archiveLesson, removeLesson, reorderLessons, reorderUnits } from '@/services/courses';

interface Lesson {
    id: number;
    title: string;
    position: number;
    content_type: string;
    content: string | null;
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
const lessonForm = useForm({
    title: '',
    position: 1,
    content_type: 'text',
    content: '',
    file: null as InstanceType<typeof globalThis.File> | null,
});
const addingLessonFor = ref<number | null>(null);
const contentOptions = [
    { value: 'text', label: 'Texto' },
    { value: 'link', label: 'Enlace' },
    { value: 'document', label: 'Documento' },
    { value: 'image', label: 'Imagen' },
    { value: 'video', label: 'Video' },
];

function createUnit() {
    unitForm.position = props.units.length + 1;
    unitForm.post(`/courses/${props.courseId}/units`, {
        preserveScroll: true,
        onSuccess: () => unitForm.reset(),
    });
}

function createLesson(unit: Unit) {
    lessonForm.position = unit.lessons.length + 1;
    lessonForm.post(`/units/${unit.id}/lessons`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            lessonForm.reset();
            addingLessonFor.value = null;
        },
    });
}

function moveUnit(index: number, offset: number) {
    const unitIds = props.units.map((unit) => unit.id);
    const target = index + offset;
    if (target < 0 || target >= unitIds.length) return;
    [unitIds[index], unitIds[target]] = [unitIds[target], unitIds[index]];
    reorderUnits(unitIds);
}

function moveLesson(unit: Unit, index: number, offset: number) {
    const lessonIds = unit.lessons.map((lesson) => lesson.id);
    const target = index + offset;
    if (target < 0 || target >= lessonIds.length) return;
    [lessonIds[index], lessonIds[target]] = [lessonIds[target], lessonIds[index]];
    reorderLessons(lessonIds);
}

function deleteUnit(unitId: number) {
    if (globalThis.confirm('¿Eliminar esta unidad y sus lecciones?')) {
        router.delete(`/units/${unitId}`, { preserveScroll: true });
    }
}

function lessonContentLabel(lesson: Lesson): string {
    if (lesson.content_type === 'text') return lesson.content ?? '';
    if (lesson.content_type === 'link' || lesson.content_type === 'video') return lesson.content ?? '';
    return lesson.content?.split('/').at(-1) ?? 'Archivo adjunto';
}
</script>

<template>
    <section>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold">Unidades y lecciones</h2>
            <form v-if="canManageContent" class="flex items-end gap-2" @submit.prevent="createUnit">
                <FormField id="new-unit-title" label="Nueva unidad" :error="unitForm.errors.title">
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
                <div class="flex items-center gap-2">
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
                        <div v-if="canManageContent" class="flex gap-2">
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
                                @click="archiveLesson(lesson.id)"
                            >
                                Archivar
                            </Button>
                            <Button variant="danger" @click="removeLesson(lesson.id)">Eliminar</Button>
                        </div>
                    </li>
                </ul>

                <div v-if="canManageContent" class="mt-3">
                    <Button
                        variant="secondary"
                        @click="addingLessonFor = addingLessonFor === unit.id ? null : unit.id"
                    >
                        Agregar lección
                    </Button>
                    <form
                        v-if="addingLessonFor === unit.id"
                        class="mt-3 grid gap-3 rounded-card bg-surface p-4 md:grid-cols-2"
                        @submit.prevent="createLesson(unit)"
                    >
                        <FormField id="lesson-title" label="Título" :error="lessonForm.errors.title">
                            <Input
                                id="lesson-title"
                                v-model="lessonForm.title"
                                :error="lessonForm.errors.title"
                            />
                        </FormField>
                        <FormField
                            id="lesson-type"
                            label="Tipo de contenido"
                            :error="lessonForm.errors.content_type"
                        >
                            <Select
                                id="lesson-type"
                                v-model="lessonForm.content_type"
                                :options="contentOptions"
                            />
                        </FormField>
                        <FormField
                            v-if="['text', 'link', 'video'].includes(lessonForm.content_type)"
                            id="lesson-content"
                            label="Contenido o URL"
                            :error="lessonForm.errors.content"
                        >
                            <Textarea
                                id="lesson-content"
                                v-model="lessonForm.content"
                                :error="lessonForm.errors.content"
                            />
                        </FormField>
                        <FormField
                            v-if="['document', 'image', 'video'].includes(lessonForm.content_type)"
                            id="lesson-file"
                            label="Archivo"
                            :error="lessonForm.errors.file"
                        >
                            <input
                                id="lesson-file"
                                type="file"
                                @input="
                                    lessonForm.file = ($event.target as HTMLInputElement).files?.[0] ?? null
                                "
                            />
                        </FormField>
                        <div class="md:col-span-2">
                            <Button type="submit" :loading="lessonForm.processing">Guardar lección</Button>
                        </div>
                    </form>
                </div>
            </li>
        </ol>
    </section>
</template>
