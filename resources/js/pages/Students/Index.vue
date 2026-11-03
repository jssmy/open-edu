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
import { submit } from '@/services/http';

interface Student {
    id: number;
    name: string;
    email: string;
    identifier: string | null;
    phone: string | null;
    is_active: boolean;
}

defineProps<{ students: Student[]; canManageStudents: boolean }>();
const creating = ref(false);
const form = useForm({
    name: '',
    email: '',
    temporary_password: '',
    identifier: '',
    phone: '',
});

function register() {
    form.post('/students', {
        preserveScroll: true,
        onSuccess: () => {
            creating.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <Head title="Estudiantes" />
    <AppLayout>
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Estudiantes</h1>
            <Button v-if="canManageStudents" @click="creating = true">Registrar estudiante</Button>
        </div>

        <EmptyState
            v-if="students.length === 0"
            title="Aún no hay estudiantes"
            description="Los estudiantes registrados aparecerán aquí."
        />
        <ul v-else class="flex flex-col gap-3">
            <li
                v-for="student in students"
                :key="student.id"
                class="flex flex-wrap items-center justify-between gap-3 rounded-card border border-border bg-panel p-4"
            >
                <div>
                    <Link :href="`/students/${student.id}`" class="font-semibold text-primary">
                        {{ student.name }}
                    </Link>
                    <p class="text-sm text-text-muted">{{ student.email }}</p>
                    <p v-if="student.identifier" class="text-xs text-text-muted">
                        Identificador: {{ student.identifier }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <Badge :variant="student.is_active ? 'success' : 'neutral'">
                        {{ student.is_active ? 'Activo' : 'Inactivo' }}
                    </Badge>
                    <button
                        v-if="canManageStudents"
                        type="button"
                        class="text-sm text-text-muted"
                        @click="
                            submit('patch', `/students/${student.id}/status`, {
                                is_active: !student.is_active,
                            })
                        "
                    >
                        {{ student.is_active ? 'Desactivar' : 'Activar' }}
                    </button>
                </div>
            </li>
        </ul>

        <Modal :open="creating" title="Registrar estudiante" @close="creating = false">
            <form class="flex flex-col gap-3" @submit.prevent="register">
                <FormField id="name" label="Nombre" :error="form.errors.name">
                    <Input id="name" v-model="form.name" :error="form.errors.name" />
                </FormField>
                <FormField id="email" label="Correo" :error="form.errors.email">
                    <Input id="email" v-model="form.email" type="email" :error="form.errors.email" />
                </FormField>
                <FormField
                    id="temporary_password"
                    label="Contraseña temporal"
                    :error="form.errors.temporary_password"
                >
                    <Input
                        id="temporary_password"
                        v-model="form.temporary_password"
                        type="password"
                        :error="form.errors.temporary_password"
                    />
                </FormField>
                <FormField id="identifier" label="Documento / identificador" :error="form.errors.identifier">
                    <Input id="identifier" v-model="form.identifier" :error="form.errors.identifier" />
                </FormField>
                <FormField id="phone" label="Teléfono" :error="form.errors.phone">
                    <Input id="phone" v-model="form.phone" :error="form.errors.phone" />
                </FormField>
                <Button type="submit" :loading="form.processing">Registrar</Button>
            </form>
        </Modal>
    </AppLayout>
</template>
