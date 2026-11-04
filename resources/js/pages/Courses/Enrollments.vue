<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import Select from '@/design-system/Select.vue';
import EnrollDialog from '@/components/enrollments/EnrollDialog.vue';
import { submit } from '@/services/http';

interface StudentEnrollment {
    id: number;
    enrollment_id?: number;
    name: string;
    email: string;
    identifier: string | null;
    phone: string | null;
    status: 'active' | 'suspended' | 'cancelled';
    group_code?: string | null;
    enrolled_at: string;
}

defineProps<{
    course: { id: number; name: string };
    students: StudentEnrollment[];
    availableStudents: { id: number; name: string; email: string; identifier: string | null }[];
    canManageEnrollments: boolean;
}>();
const enrolling = ref(false);
const statusOptions = [
    { value: 'active', label: 'Activa' },
    { value: 'suspended', label: 'Suspendida' },
    { value: 'cancelled', label: 'Cancelada' },
];

function changeStatus(enrollmentId: number, status: string) {
    submit('patch', `/enrollments/${enrollmentId}/status`, { status });
}
</script>

<template>
    <Head :title="`Inscritos · ${course.name}`" />
    <AppLayout>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <Link :href="`/courses/${course.id}`" class="text-sm text-primary">Volver al curso</Link>
                <h1 class="text-xl font-semibold">Inscritos · {{ course.name }}</h1>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Link
                    v-if="canManageEnrollments"
                    href="/enrollments"
                    class="inline-flex min-h-11 items-center rounded-control border border-border bg-panel px-4 py-2 text-sm font-medium text-text hover:bg-surface"
                >
                    Ir al módulo de matrículas
                </Link>
                <Button v-if="canManageEnrollments" @click="enrolling = true">Inscribir estudiante</Button>
            </div>
        </div>

        <EmptyState v-if="students.length === 0" title="Aún no hay estudiantes inscritos" />
        <ul v-else class="flex flex-col gap-3">
            <li
                v-for="student in students"
                :key="student.id"
                class="flex flex-wrap items-center justify-between gap-3 rounded-card border border-border bg-panel p-4"
            >
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Link :href="`/students/${student.id}`" class="font-semibold text-primary">
                            {{ student.name }}
                        </Link>
                        <Badge v-if="student.group_code" variant="neutral">{{ student.group_code }}</Badge>
                    </div>
                    <p class="text-sm text-text-muted">{{ student.email }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <Badge
                        :variant="
                            student.status === 'active'
                                ? 'success'
                                : student.status === 'suspended'
                                  ? 'warning'
                                  : 'neutral'
                        "
                    >
                        {{ student.status }}
                    </Badge>
                    <Select
                        v-if="canManageEnrollments && student.status !== 'cancelled'"
                        :id="`enrollment-status-${student.id}`"
                        :model-value="student.status"
                        :options="statusOptions"
                        :aria-label="`Estado de ${student.name}`"
                        @update:model-value="changeStatus(student.id, String($event))"
                    />
                </div>
            </li>
        </ul>

        <EnrollDialog
            :open="enrolling"
            :course-id="course.id"
            :students="availableStudents"
            @close="enrolling = false"
        />
    </AppLayout>
</template>
