<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/design-system/Badge.vue';
import IconButton from '@/design-system/IconButton.vue';
import FormField from '@/design-system/FormField.vue';
import Select from '@/design-system/Select.vue';
import { requestAlertDialog } from '@/composables/useAlertDialog';
import { useCourses } from '@/composables/useCourses';
import {
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
}>();
const { statusLabel, statusVariant } = useCourses();
const lessonCount = computed(() =>
    props.course.units.reduce(
        (total, unit) => total + unit.lessons.filter((lesson) => !lesson.is_archived).length,
        0,
    ),
);
const statusForm = useForm<{ status: CourseStatus }>({ status: props.course.status });
const statusOptions = [
    { value: 'draft', label: 'Borrador' },
    { value: 'published', label: 'Publicado' },
    { value: 'deactivated', label: 'Desactivado' },
];

function saveStatus() {
    changeCourseStatus(props.course.id, statusForm.status);
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
                    <IconButton
                        v-if="canManageCourses"
                        icon="edit"
                        label="Editar curso" show-label
                        :href="`/courses/${course.id}/edit`"
                        variant="primary"
                    />
                    <IconButton
                        v-if="canManageCourses"
                        icon="users"
                        label="Inscripciones" show-label
                        :href="`/courses/${course.id}/students`"
                    />
                    <IconButton
                        v-if="canManageContent"
                        icon="plus"
                        label="Nueva evaluación" show-label
                        :href="`/courses/${course.id}/assessments/create`"
                        variant="success"
                    />
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
                    <div class="flex justify-end">
                        <IconButton icon="save" label="Actualizar estado" show-label variant="primary" @click="saveStatus" />
                    </div>
                </section>

                <section
                    class="flex flex-col gap-3 rounded-card border border-border bg-panel p-5 shadow-card"
                    aria-labelledby="course-teachers-heading"
                >
                    <h2 id="course-teachers-heading" class="font-semibold">Docentes asignados</h2>
                    <p v-if="course.teachers.length === 0" class="text-sm text-text-muted">
                        Sin docentes programados.
                    </p>
                    <ul v-else class="space-y-1.5 text-sm">
                        <li
                            v-for="teacher in course.teachers"
                            :key="teacher.id"
                            class="rounded-control border border-border bg-surface px-3 py-2"
                        >
                            {{ teacher.name }}
                        </li>
                    </ul>
                    <p class="text-xs text-text-muted">
                        La asignación de docentes y horarios se gestiona desde Programación académica.
                    </p>
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
                        <IconButton
                            v-if="canManageContent"
                            icon="edit"
                            :label="`Editar evaluación ${assessment.title}`"
                            :href="`/assessments/${assessment.id}/edit`"
                            variant="primary"
                        />
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
                    <IconButton icon="delete" label="Eliminar curso" show-label variant="danger" @click="deleteCourse" />
                </section>
            </aside>
        </div>
    </AppLayout>
</template>
