<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Button from '@/design-system/Button.vue';
import FormField from '@/design-system/FormField.vue';
import Modal from '@/design-system/Modal.vue';
import Select from '@/design-system/Select.vue';

const props = defineProps<{
    open: boolean;
    courseId: number;
    students: { id: number; name: string; email: string; identifier: string | null }[];
}>();
const emit = defineEmits<{ close: [] }>();
const form = useForm({ student_id: '' });
const options = props.students.map((student) => ({
    value: String(student.id),
    label: `${student.name} · ${student.email}`,
}));

function enroll() {
    form.post(`/courses/${props.courseId}/enrollments`, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
}
</script>

<template>
    <Modal :open="open" title="Inscribir estudiante" @close="emit('close')">
        <form class="flex flex-col gap-4" @submit.prevent="enroll">
            <FormField id="student_id" label="Estudiante" :error="form.errors.student_id">
                <Select id="student_id" v-model="form.student_id" :options="options" />
            </FormField>
            <p v-if="students.length === 0" class="text-sm text-text-muted">
                No hay estudiantes activos disponibles para inscribir.
            </p>
            <div class="flex items-center justify-end gap-2 pt-2">
                <Button type="button" variant="secondary" @click="emit('close')">Cancelar</Button>
                <Button type="submit" :loading="form.processing" :disabled="students.length === 0">
                    Inscribir
                </Button>
            </div>
        </form>
    </Modal>
</template>
