<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import FormField from '@/design-system/FormField.vue';
import Select from '@/design-system/Select.vue';
import { requestAlertDialog } from '@/composables/useAlertDialog';
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
const lessonCount = computed(() =>
    props.course.units.reduce(
        (total, unit) => total + unit.lessons.filter((lesson) => !lesson.is_archived).length,
        0,
    ),
);
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

async function deleteCourse() {
    if (
        await requestAlertDialog({
            variant: 'danger',
            title: '¿Eliminar este curso?',
            message:
                'El historial y las inscripciones existentes se conservarán. Esta acción no se puede deshacer.',
            confirmLabel: 'Eliminar curso',
        })
    ) {
        removeCourse(props.course.id);
    }
}
</script>

<template>
    <Head :title="course.name" />
    <AppLayout>
        <header class="mb-6 overflow-hidden rounded-card border border-border bg-panel shadow-card">
            <div class="h-2 bg-gradient-to-r from-primary via-primary to-secondary" />
            <div class="flex flex-wrap items-start justify-between gap-4 p-5 sm:p-6">
                <div class="min-w-0 flex-1 basis-72">
                    <Link href="/courses" class="text-sm text-text-muted hover:text-primary">← Cursos</Link>
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold tracking-tight">{{ course.name }}</h1>
                        <Badge :variant="statusVariant(course.status)">{{
                            statusLabel(course.status)
                        }}</Badge>
                    </div>
                    <dl class="mt-4 grid grid-cols-3 gap-3 sm:max-w-md">
                        <div class="rounded-control bg-surface p-3">
                            <dt class="text-xs text-text-muted">Unidades</dt>
                            <dd class="text-lg font-semibold text-primary">{{ course.units.length }}</dd>
                        </div>
                        <div class="rounded-control bg-surface p-3">
                            <dt class="text-xs text-text-muted">Lecciones</dt>
                            <dd class="text-lg font-semibold text-primary">{{ lessonCount }}</dd>
                        </div>
                        <div class="rounded-control bg-surface p-3">
                            <dt class="text-xs text-text-muted">Evaluaciones</dt>
                            <dd class="text-lg font-semibold text-primary">
                                {{ course.assessments.length }}
                            </dd>
                        </div>
                    </dl>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-if="canManageCourses"
                        :href="`/courses/${course.id}/edit`"
                        class="inline-flex min-h-11 items-center rounded-control bg-primary px-4 text-sm font-medium text-white transition hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                    >
                        Editar curso
                    </Link>
                    <Link
                        v-if="canManageCourses"
                        :href="`/courses/${course.id}/students`"
                        class="inline-flex min-h-11 items-center rounded-control border border-border bg-panel px-4 text-sm font-medium text-text transition hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    >
                        Inscripciones
                    </Link>
                    <Link
                        v-if="canManageContent"
                        :href="`/courses/${course.id}/assessments/create`"
                        class="inline-flex min-h-11 items-center rounded-control border border-border bg-panel px-4 text-sm font-medium text-text transition hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    >
                        Nueva evaluación
                    </Link>
                </div>
            </div>
        </header>

        <div class="grid items-start gap-6 lg:grid-cols-3">
            <div class="flex min-w-0 flex-col gap-6 lg:col-span-2">
                <section
                    v-if="course.description || course.objectives"
                    class="grid gap-6 rounded-card border border-border bg-panel p-5 shadow-card sm:p-6"
                >
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

                <section
                    v-if="course.intro_video_embed_url"
                    class="rounded-card border border-border bg-panel p-5 shadow-card"
                    aria-labelledby="course-video-heading"
                >
                    <h2 id="course-video-heading" class="mb-3 text-lg font-semibold">
                        Presentación del curso
                    </h2>
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
                        class="aspect-video w-full rounded-control border border-border"
                    />
                </section>

                <CourseContentEditor
                    :course-id="course.id"
                    :units="course.units"
                    :can-manage-content="canManageContent"
                />
            </div>

            <aside class="flex min-w-0 flex-col gap-6">
                <section
                    v-if="canManageCourses"
                    class="flex flex-col gap-4 rounded-card border border-border bg-panel p-5 shadow-card"
                >
                    <h2 class="font-semibold">Publicación</h2>
                    <FormField id="course-status" label="Estado">
                        <Select id="course-status" v-model="statusForm.status" :options="statusOptions" />
                    </FormField>
                    <Button variant="secondary" @click="saveStatus">Actualizar estado</Button>
                </section>

                <section
                    v-if="canManageCourses"
                    class="flex flex-col gap-3 rounded-card border border-border bg-panel p-5 shadow-card"
                >
                    <h2 class="font-semibold">Docentes asignados</h2>
                    <label
                        v-for="teacher in availableTeachers"
                        :key="teacher.id"
                        class="flex min-h-11 items-center gap-3 rounded-control border border-border px-3 text-sm hover:bg-surface"
                    >
                        <input
                            v-model="selectedTeachers"
                            type="checkbox"
                            :value="teacher.id"
                            class="size-4"
                        />
                        {{ teacher.name }}
                    </label>
                    <p v-if="availableTeachers.length === 0" class="text-sm text-text-muted">
                        No hay docentes disponibles.
                    </p>
                    <Button variant="secondary" @click="saveTeachers">Guardar docentes</Button>
                </section>

                <section
                    class="flex flex-col gap-3 rounded-card border border-border bg-panel p-5 shadow-card"
                    aria-labelledby="assessments-heading"
                >
                    <h2 id="assessments-heading" class="font-semibold">Evaluaciones</h2>
                    <p v-if="course.assessments.length === 0" class="text-sm text-text-muted">
                        Aún no hay evaluaciones.
                    </p>
                    <div
                        v-for="assessment in course.assessments"
                        :key="assessment.id"
                        class="flex flex-wrap items-center justify-between gap-2 rounded-control border border-border p-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ assessment.title }}</p>
                            <Badge :variant="assessment.is_published ? 'success' : 'neutral'">
                                {{ assessment.is_published ? 'Publicada' : 'Borrador' }}
                            </Badge>
                        </div>
                        <Link
                            v-if="canManageContent"
                            :href="`/assessments/${assessment.id}/edit`"
                            class="text-sm font-medium text-primary hover:underline"
                        >
                            Editar
                        </Link>
                    </div>
                </section>

                <section
                    v-if="canManageCourses"
                    class="rounded-card border border-danger/30 bg-danger-soft p-5"
                >
                    <h2 class="font-semibold text-danger">Zona de peligro</h2>
                    <p class="mb-3 mt-1 text-sm text-text-muted">
                        El historial y las inscripciones existentes se conservan.
                    </p>
                    <Button variant="danger" @click="deleteCourse">Eliminar curso</Button>
                </section>
            </aside>
        </div>
    </AppLayout>
</template>
