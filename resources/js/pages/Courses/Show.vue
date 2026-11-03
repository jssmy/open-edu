<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import FormField from '@/design-system/FormField.vue';
import Select from '@/design-system/Select.vue';
import { useCourses } from '@/composables/useCourses';
import {
    assignCourseTeachers,
    changeCourseStatus,
    removeCourse,
    type CourseStatus,
} from '@/services/courses';
import CourseContentEditor from '@/components/courses/CourseContentEditor.vue';
import RichTextContent from '@/components/editor/RichTextContent.vue';
import type { RichTextDocument } from '@/types/richText';

interface Course {
    id: number;
    name: string;
    description: RichTextDocument | null;
    objectives: RichTextDocument | null;
    intro_video_url: string | null;
    intro_video_embed_url: string | null;
    status: CourseStatus;
    teachers: { id: number; name: string }[];
    units: {
        id: number;
        title: string;
        position: number;
        lessons: {
            id: number;
            title: string;
            position: number;
            content_type: 'text' | 'document' | 'image' | 'link' | 'video';
            content: RichTextDocument | string | null;
            is_archived: boolean;
        }[];
    }[];
    assessments: { id: number; title: string; is_published: boolean }[];
}

const props = defineProps<{
    course: Course;
    canManageCourses: boolean;
    canManageContent: boolean;
    availableTeachers: { id: number; name: string }[];
}>();
const { statusLabel, statusVariant } = useCourses();
const statusForm = useForm<{ status: CourseStatus }>({ status: props.course.status });
const selectedTeachers = ref(props.course.teachers.map((teacher) => teacher.id));
const statusOptions = [
    { value: 'draft', label: 'Borrador' },
    { value: 'published', label: 'Publicado' },
    { value: 'deactivated', label: 'Desactivado' },
];

function saveStatus() {
    changeCourseStatus(props.course.id, statusForm.status);
}

function saveTeachers() {
    assignCourseTeachers(props.course.id, selectedTeachers.value);
}

function deleteCourse() {
    if (
        globalThis.confirm('Eliminar este curso. El historial y las inscripciones existentes se conservarán.')
    ) {
        removeCourse(props.course.id);
    }
}
</script>

<template>
    <Head :title="course.name" />
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold">{{ course.name }}</h1>
                <Badge :variant="statusVariant(course.status)">{{ statusLabel(course.status) }}</Badge>
                <div class="mt-2 flex flex-wrap gap-4">
                    <Link
                        v-if="canManageCourses"
                        :href="`/courses/${course.id}/edit`"
                        class="text-sm text-primary hover:underline"
                    >
                        Editar curso
                    </Link>
                    <Link
                        v-if="canManageCourses"
                        :href="`/courses/${course.id}/students`"
                        class="text-sm text-primary"
                    >
                        Gestionar inscripciones
                    </Link>
                    <Link
                        v-if="canManageContent"
                        :href="`/courses/${course.id}/assessments/create`"
                        class="text-sm text-primary"
                    >
                        Nueva evaluación
                    </Link>
                </div>
            </div>
            <Button v-if="canManageCourses" variant="danger" @click="deleteCourse">Eliminar curso</Button>
        </div>

        <section
            v-if="canManageCourses"
            class="mb-8 grid gap-6 rounded-card border border-border bg-panel p-5"
        >
            <div class="flex flex-col gap-3">
                <h2 class="font-semibold">Estado y docentes</h2>
                <FormField id="course-status" label="Estado">
                    <Select id="course-status" v-model="statusForm.status" :options="statusOptions" />
                </FormField>
                <Button variant="secondary" @click="saveStatus">Actualizar estado</Button>
                <fieldset class="flex flex-col gap-2">
                    <legend class="text-sm font-medium">Docentes asignados</legend>
                    <label
                        v-for="teacher in availableTeachers"
                        :key="teacher.id"
                        class="flex items-center gap-2 text-sm"
                    >
                        <input v-model="selectedTeachers" type="checkbox" :value="teacher.id" />
                        {{ teacher.name }}
                    </label>
                    <p v-if="availableTeachers.length === 0" class="text-sm text-text-muted">
                        No hay docentes disponibles.
                    </p>
                </fieldset>
                <Button variant="secondary" @click="saveTeachers">Guardar docentes</Button>
            </div>
        </section>
        <section class="mb-8 rounded-card border border-border bg-panel p-5">
            <RichTextContent
                v-if="course.description"
                :content="course.description"
                label="Descripción del curso"
            />
            <RichTextContent
                v-if="course.objectives"
                :content="course.objectives"
                label="Objetivos del curso"
            />
        </section>

        <section v-if="course.intro_video_embed_url" class="mb-8" aria-labelledby="course-video-heading">
            <h2 id="course-video-heading" class="mb-3 text-lg font-semibold">Presentación del curso</h2>
            <iframe
                :src="course.intro_video_embed_url"
                :title="`Video de presentación: ${course.name}`"
                referrerpolicy="no-referrer"
                allow="
                    accelerometer;
                    autoplay;
                    clipboard-write;
                    encrypted-media;
                    gyroscope;
                    picture-in-picture;
                    web-share;
                "
                allowfullscreen
                loading="lazy"
                class="aspect-video w-full rounded-card border border-border"
            />
        </section>

        <CourseContentEditor
            :course-id="course.id"
            :units="course.units"
            :can-manage-content="canManageContent"
        />

        <section
            v-if="course.assessments.length > 0"
            class="mt-8 flex flex-col gap-3"
            aria-labelledby="assessments-heading"
        >
            <h2 id="assessments-heading" class="text-xl font-semibold">Evaluaciones</h2>
            <div class="flex flex-col gap-3">
                <div
                    v-for="assessment in course.assessments"
                    :key="assessment.id"
                    class="flex flex-wrap items-center justify-between gap-3 rounded-card border border-border bg-panel p-4"
                >
                    <div class="flex flex-col gap-1">
                        <span class="font-medium">{{ assessment.title }}</span>
                        <span class="text-sm text-text-muted">
                            {{ assessment.is_published ? 'Publicada' : 'Borrador' }}
                        </span>
                    </div>
                    <Link
                        v-if="canManageContent"
                        :href="`/assessments/${assessment.id}/edit`"
                        class="text-sm font-medium text-primary hover:underline"
                    >
                        Editar evaluación
                    </Link>
                </div>
            </div>
        </section>
    </AppLayout>
</template>
