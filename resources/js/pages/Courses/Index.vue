<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import FormField from '@/design-system/FormField.vue';
import Input from '@/design-system/Input.vue';
import Modal from '@/design-system/Modal.vue';
import Textarea from '@/design-system/Textarea.vue';
import { useCourses } from '@/composables/useCourses';

interface CourseRow {
    id: number;
    name: string;
    description: string | null;
    objectives: string | null;
    status: 'draft' | 'published' | 'deactivated';
    teachers: { id: number; name: string }[];
}

defineProps<{ courses: CourseRow[]; canManageCourses: boolean }>();
const { statusLabel, statusVariant } = useCourses();
const creating = ref(false);
const form = useForm({ name: '', description: '', objectives: '' });

function createCourse() {
    form.post('/courses', {
        preserveScroll: true,
        onSuccess: () => {
            creating.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <Head title="Cursos" />
    <AppLayout>
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Cursos</h1>
            <Button v-if="canManageCourses" @click="creating = true">Nuevo curso</Button>
        </div>

        <EmptyState
            v-if="courses.length === 0"
            title="Aún no hay cursos"
            description="Los cursos disponibles aparecerán aquí."
        />
        <ul v-else class="flex flex-col gap-3">
            <li
                v-for="course in courses"
                :key="course.id"
                class="rounded-card border border-border bg-panel p-4"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <Link :href="`/courses/${course.id}`" class="font-semibold text-primary">
                            {{ course.name }}
                        </Link>
                        <p class="text-sm text-text-muted">
                            {{ course.teachers.map((teacher) => teacher.name).join(', ') }}
                        </p>
                    </div>
                    <Badge :variant="statusVariant(course.status)">{{ statusLabel(course.status) }}</Badge>
                </div>
            </li>
        </ul>

        <Modal :open="creating" title="Nuevo curso" @close="creating = false">
            <form id="create-course" class="flex flex-col gap-3" @submit.prevent="createCourse">
                <FormField id="name" label="Nombre" :error="form.errors.name">
                    <Input id="name" v-model="form.name" :error="form.errors.name" />
                </FormField>
                <FormField id="description" label="Descripción" :error="form.errors.description">
                    <Textarea id="description" v-model="form.description" :error="form.errors.description" />
                </FormField>
                <FormField id="objectives" label="Objetivos" :error="form.errors.objectives">
                    <Textarea id="objectives" v-model="form.objectives" :error="form.errors.objectives" />
                </FormField>
            </form>
            <template #actions>
                <Button variant="secondary" @click="creating = false">Cancelar</Button>
                <Button type="submit" :loading="form.processing" @click="createCourse">Crear curso</Button>
            </template>
        </Modal>
    </AppLayout>
</template>
