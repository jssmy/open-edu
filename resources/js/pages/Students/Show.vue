<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import Card from '@/design-system/Card.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import FormField from '@/design-system/FormField.vue';
import Input from '@/design-system/Input.vue';
import { submit } from '@/services/http';

interface Student {
    id: number;
    name: string;
    email: string;
    identifier: string | null;
    phone: string | null;
    is_active: boolean;
}

interface Course {
    id: number;
    enrollment_id?: number;
    name: string;
    status: string;
    group_code?: string | null;
    enrolled_at: string;
}

const props = defineProps<{
    student: Student;
    courses: Course[];
    canManageStudents: boolean;
    canManageEnrollments?: boolean;
}>();
const form = useForm({
    name: props.student.name,
    email: props.student.email,
    identifier: props.student.identifier ?? '',
    phone: props.student.phone ?? '',
});
</script>

<template>
    <Head :title="student.name" />
    <AppLayout>
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold">{{ student.name }}</h1>
                <Badge :variant="student.is_active ? 'success' : 'neutral'">
                    {{ student.is_active ? 'Activo' : 'Inactivo' }}
                </Badge>
            </div>
            <Button
                v-if="canManageStudents"
                variant="secondary"
                @click="
                    submit('patch', `/students/${student.id}/status`, {
                        is_active: !student.is_active,
                    })
                "
            >
                {{ student.is_active ? 'Desactivar' : 'Activar' }}
            </Button>
        </div>

        <Card v-if="canManageStudents" class="mb-6 max-w-2xl">
            <form class="grid gap-4 md:grid-cols-2" @submit.prevent="form.put(`/students/${student.id}`)">
                <FormField id="name" label="Nombre" :error="form.errors.name">
                    <Input id="name" v-model="form.name" :error="form.errors.name" />
                </FormField>
                <FormField id="email" label="Correo" :error="form.errors.email">
                    <Input id="email" v-model="form.email" type="email" :error="form.errors.email" />
                </FormField>
                <FormField id="identifier" label="Documento / identificador" :error="form.errors.identifier">
                    <Input id="identifier" v-model="form.identifier" :error="form.errors.identifier" />
                </FormField>
                <FormField id="phone" label="Teléfono" :error="form.errors.phone">
                    <Input id="phone" v-model="form.phone" :error="form.errors.phone" />
                </FormField>
                <Button type="submit" :loading="form.processing" class="md:col-span-2"
                    >Guardar cambios</Button
                >
            </form>
        </Card>

        <section>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-semibold">Cursos</h2>
                <Link
                    v-if="canManageEnrollments"
                    :href="`/enrollments?student_id=${student.id}`"
                    class="text-sm font-medium text-primary hover:underline"
                >
                    Gestionar matrícula y horario →
                </Link>
            </div>
            <EmptyState v-if="courses.length === 0" title="Sin cursos inscritos" />
            <ul v-else class="flex flex-col gap-2">
                <li
                    v-for="course in courses"
                    :key="course.id"
                    class="flex flex-wrap items-center justify-between gap-2 rounded-card border border-border bg-panel p-3"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <Link :href="`/courses/${course.id}`" class="font-medium text-primary">
                            {{ course.name }}
                        </Link>
                        <Badge v-if="course.group_code" variant="neutral">{{ course.group_code }}</Badge>
                    </div>
                    <span class="text-sm text-text-muted">
                        {{ course.status }} · {{ new Date(course.enrolled_at).toLocaleDateString() }}
                    </span>
                </li>
            </ul>
        </section>
    </AppLayout>
</template>
