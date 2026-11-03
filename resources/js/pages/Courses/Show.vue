<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import FormField from '@/design-system/FormField.vue';
import Input from '@/design-system/Input.vue';
import Select from '@/design-system/Select.vue';
import Textarea from '@/design-system/Textarea.vue';
import { useCourses } from '@/composables/useCourses';
import {
    assignCourseTeachers,
    changeCourseStatus,
    removeCourse,
    type CourseStatus,
} from '@/services/courses';
import CourseContentEditor from '@/components/courses/CourseContentEditor.vue';

interface Course {
    id: number;
    name: string;
    description: string | null;
    objectives: string | null;
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
            content_type: string;
            content: string | null;
            is_archived: boolean;
        }[];
    }[];
}

const props = defineProps<{
    course: Course;
    canManageCourses: boolean;
    canManageContent: boolean;
    availableTeachers: { id: number; name: string }[];
}>();
const { statusLabel, statusVariant } = useCourses();
const form = useForm({
    name: props.course.name,
    description: props.course.description ?? '',
    objectives: props.course.objectives ?? '',
});
const statusForm = useForm<{ status: CourseStatus }>({ status: props.course.status });
const selectedTeachers = ref(props.course.teachers.map((teacher) => teacher.id));
const statusOptions = [
    { value: 'draft', label: 'Borrador' },
    { value: 'published', label: 'Publicado' },
    { value: 'deactivated', label: 'Desactivado' },
];

function saveCourse() {
    form.put(`/courses/${props.course.id}`, { preserveScroll: true });
}

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
                <div v-if="canManageCourses" class="mt-2">
                    <Link :href="`/courses/${course.id}/students`" class="text-sm text-primary">
                        Gestionar inscripciones
                    </Link>
                </div>
            </div>
            <Button v-if="canManageCourses" variant="danger" @click="deleteCourse">Eliminar curso</Button>
        </div>

        <section
            v-if="canManageCourses"
            class="mb-8 grid gap-6 rounded-card border border-border bg-panel p-5 md:grid-cols-2"
        >
            <form class="flex flex-col gap-3" @submit.prevent="saveCourse">
                <h2 class="font-semibold">Datos del curso</h2>
                <FormField id="name" label="Nombre" :error="form.errors.name">
                    <Input id="name" v-model="form.name" :error="form.errors.name" />
                </FormField>
                <FormField id="description" label="Descripción" :error="form.errors.description">
                    <Textarea id="description" v-model="form.description" :error="form.errors.description" />
                </FormField>
                <FormField id="objectives" label="Objetivos" :error="form.errors.objectives">
                    <Textarea id="objectives" v-model="form.objectives" :error="form.errors.objectives" />
                </FormField>
                <Button type="submit" :loading="form.processing">Guardar cambios</Button>
            </form>

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
        <section v-else class="mb-8 rounded-card border border-border bg-panel p-5">
            <p>{{ course.description }}</p>
            <p class="mt-2 text-sm text-text-muted">{{ course.objectives }}</p>
        </section>

        <CourseContentEditor
            :course-id="course.id"
            :units="course.units"
            :can-manage-content="canManageContent"
        />
    </AppLayout>
</template>
