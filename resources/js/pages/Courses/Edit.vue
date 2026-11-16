<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Button from '@/design-system/Button.vue';
import FormField from '@/design-system/FormField.vue';
import FileInput from '@/design-system/FileInput.vue';
import Input from '@/design-system/Input.vue';
import Modal from '@/design-system/Modal.vue';
import Select from '@/design-system/Select.vue';
import RichTextEditor from '@/components/editor/RichTextEditor.vue';
import { useUnsavedChangesGuard } from '@/composables/useUnsavedChangesGuard';
import { emptyRichTextDocument, type RichTextDocument } from '@/types/richText';

interface CategoryOption {
    id: number;
    name: string;
    slug: string;
    role_name?: string;
}

interface RoleOption {
    value: string;
    label: string;
}

interface Course {
    id: number;
    name: string;
    category_id?: number | null;
    issues_certificate?: boolean;
    description: RichTextDocument | null;
    objectives: RichTextDocument | null;
    intro_video_url: string | null;
    cover_url: string;
    has_custom_cover: boolean;
    lock_version: number;
}

const props = withDefaults(
    defineProps<{
        course: Course | null;
        categories?: CategoryOption[];
        roles?: RoleOption[];
    }>(),
    {
        categories: () => [],
        roles: () => [
            { value: 'estudiante', label: 'Estudiante' },
            { value: 'docente', label: 'Docente' },
        ],
    },
);

const form = useForm({
    name: props.course?.name ?? '',
    category_id: props.course?.category_id ? String(props.course.category_id) : '',
    issues_certificate: props.course?.issues_certificate ?? true,
    description: props.course?.description ?? emptyRichTextDocument(),
    objectives: props.course?.objectives ?? emptyRichTextDocument(),
    intro_video_url: props.course?.intro_video_url ?? '',
    lock_version: props.course?.lock_version ?? null,
    cover_image: null as globalThis.File | null,
    remove_cover: false,
});

const roleLabels: Record<string, string> = {
    estudiante: 'Estudiante',
    docente: 'Docente',
    administrador: 'Administrador',
};

const categoryOptions = computed(() => [
    { value: '', label: 'Seleccionar categoría...' },
    ...props.categories.map((cat) => ({
        value: String(cat.id),
        label: cat.role_name
            ? `${cat.name} (${roleLabels[cat.role_name] ?? cat.role_name})`
            : cat.name,
    })),
]);

const roleOptions = computed(() =>
    props.roles && props.roles.length > 0
        ? props.roles
        : [
              { value: 'estudiante', label: 'Estudiante' },
              { value: 'docente', label: 'Docente' },
          ],
);

const isCategoryModalOpen = ref(false);
const categoryName = ref('');
const categoryRoleName = ref('estudiante');
const categoryForm = useForm({
    name: '',
    role_name: 'estudiante',
});
const categoryNameError = computed(() => categoryForm.errors.name);
const categoryRoleError = computed(() => categoryForm.errors.role_name);
const isCreatingCategory = computed(() => categoryForm.processing);

function openCategoryModal(): void {
    categoryName.value = '';
    categoryRoleName.value = 'estudiante';
    categoryForm.reset();
    categoryForm.clearErrors();
    isCategoryModalOpen.value = true;
}

function closeCategoryModal(): void {
    isCategoryModalOpen.value = false;
    categoryName.value = '';
    categoryRoleName.value = 'estudiante';
    categoryForm.reset();
    categoryForm.clearErrors();
}

function submitCategory(): void {
    categoryForm.name = categoryName.value;
    categoryForm.role_name = categoryRoleName.value || 'estudiante';
    categoryForm.post('/course-categories', {
        preserveScroll: true,
        onSuccess: () => {
            closeCategoryModal();
        },
    });
}

const selectedCover = computed(() => form.cover_image as globalThis.File | null);
const coverPreview = ref<string | null>(null);
const currentCoverUrl = computed(() => {
    if (coverPreview.value) {
        return coverPreview.value;
    }

    return props.course && !form.remove_cover && props.course.has_custom_cover
        ? props.course.cover_url
        : null;
});

function onCoverSelected(file: globalThis.File | null): void {
    form.cover_image = file;
    form.remove_cover = false;
    if (coverPreview.value) {
        globalThis.URL.revokeObjectURL(coverPreview.value);
    }
    coverPreview.value = file ? globalThis.URL.createObjectURL(file) : null;
}

function removeCover(): void {
    form.cover_image = null;
    form.remove_cover = true;
    coverPreview.value = null;
}
const requestError = ref('');
const isSubmitting = ref(false);
const introVideoUrl = computed({
    get: () => form.intro_video_url,
    set: (url: string) => {
        form.intro_video_url = url;
    },
});
const introVideoUrlError = computed(() => form.errors.intro_video_url);
const coverError = computed(() => form.errors.cover_image);
const isProcessing = computed(() => form.processing);

useUnsavedChangesGuard(() => form.isDirty && !isSubmitting.value);

/** El formulario viaja como multipart (por la portada): los documentos Tiptap se serializan como JSON para conservar sus tipos. */
function encodeForMultipart<
    T extends {
        description: unknown;
        objectives: unknown;
        category_id: string | number | null;
        issues_certificate: boolean | number;
    },
>(data: T): Record<string, unknown> {
    return {
        ...data,
        category_id: data.category_id === '' ? null : Number(data.category_id),
        issues_certificate: data.issues_certificate ? 1 : 0,
        description: JSON.stringify(data.description),
        objectives: JSON.stringify(data.objectives),
        rich_text_encoded: true,
    };
}

function saveCourse(): void {
    requestError.value = '';
    isSubmitting.value = true;

    const options = {
        preserveScroll: true,
        onFinish: () => {
            isSubmitting.value = false;
        },
        onHttpException: (response: { status: number }) => {
            if (response.status !== 409) {
                return;
            }

            requestError.value =
                'Este curso fue actualizado por otra persona. Tus cambios siguen aquí; copia tu contenido y recarga para revisar la versión más reciente.';

            return false;
        },
    };

    if (props.course) {
        form.transform(({ lock_version, ...data }) => ({
            ...encodeForMultipart(data),
            lock_version: lock_version ?? 0,
        }));
        form.put(`/courses/${props.course.id}`, { ...options, forceFormData: true });
    } else {
        form.transform((data) => {
            const payload = encodeForMultipart(data);
            Reflect.deleteProperty(payload, 'lock_version');
            Reflect.deleteProperty(payload, 'remove_cover');

            return payload;
        });
        form.post('/courses', { ...options, forceFormData: true });
    }
}
</script>

<template>
    <Head :title="course ? 'Editar curso' : 'Nuevo curso'" />
    <AppLayout>
        <main class="mx-auto flex w-full max-w-4xl flex-col gap-6">
            <header class="flex flex-col gap-2">
                <Link
                    :href="course ? `/courses/${course.id}` : '/courses'"
                    class="w-fit text-sm text-primary hover:underline"
                >
                    {{ course ? 'Volver al curso' : 'Volver a cursos' }}
                </Link>
                <h1 class="text-2xl font-semibold">{{ course ? 'Editar curso' : 'Nuevo curso' }}</h1>
            </header>

            <Alert v-if="requestError" variant="danger">{{ requestError }}</Alert>

            <form
                class="flex flex-col gap-5 rounded-card border border-border bg-panel p-5"
                @submit.prevent="saveCourse"
            >
                <FormField id="course-name" label="Nombre" :error="form.errors.name">
                    <Input id="course-name" v-model="form.name" :error="form.errors.name" />
                </FormField>

                <FormField
                    id="course-category"
                    label="Categoría del curso"
                    :error="form.errors.category_id"
                >
                    <div class="flex items-center gap-2">
                        <div class="flex-1">
                            <Select
                                id="course-category"
                                v-model="form.category_id"
                                :options="categoryOptions"
                            />
                        </div>
                        <Button
                            type="button"
                            variant="secondary"
                            size="sm"
                            data-testid="inline-create-category-btn"
                            @click="openCategoryModal"
                        >
                            + Nueva categoría
                        </Button>
                    </div>
                </FormField>

                <div class="rounded-control border border-border bg-surface/50 p-4">
                    <label
                        for="course-issues-certificate"
                        class="flex cursor-pointer items-start gap-3"
                    >
                        <input
                            id="course-issues-certificate"
                            v-model="form.issues_certificate"
                            type="checkbox"
                            class="mt-0.5 size-4 rounded border-border text-primary focus:ring-primary"
                        />
                        <div class="flex flex-col">
                            <span class="text-sm font-medium text-text">
                                Emitir certificado al finalizar el curso
                            </span>
                            <span class="text-xs text-text-muted">
                                Cuando está marcado, el participante podrá generar y descargar su certificado en PDF al completar el curso.
                            </span>
                        </div>
                    </label>
                </div>

                <FormField id="course-description" label="Descripción" :error="form.errors.description">
                    <RichTextEditor
                        id="course-description"
                        v-model="form.description"
                        label="Descripción"
                        :course-id="course?.id"
                        :error="form.errors.description"
                    />
                </FormField>
                <FormField id="course-objectives" label="Objetivos" :error="form.errors.objectives">
                    <RichTextEditor
                        id="course-objectives"
                        v-model="form.objectives"
                        label="Objetivos"
                        :course-id="course?.id"
                        :error="form.errors.objectives"
                    />
                </FormField>
                <FormField
                    id="intro-video-url"
                    label="Video de presentación (YouTube o Vimeo)"
                    :error="form.errors.intro_video_url"
                >
                    <Input
                        id="intro-video-url"
                        v-model="introVideoUrl"
                        type="url"
                        placeholder="https://"
                        :error="introVideoUrlError"
                    />
                </FormField>
                <FormField
                    id="course-cover"
                    label="Imagen de portada (JPEG, PNG o WebP, máx. 5 MB)"
                    :error="coverError"
                >
                    <div class="flex flex-wrap items-start gap-4">
                        <img
                            v-if="currentCoverUrl"
                            :src="currentCoverUrl"
                            alt="Vista previa de la portada"
                            class="aspect-video w-56 rounded-control border border-border object-cover"
                        />
                        <p
                            v-else
                            class="w-56 rounded-control border border-dashed border-border p-3 text-sm text-text-muted"
                        >
                            Sin imagen: se usará una portada generada por el sistema.
                        </p>
                        <div class="flex flex-col gap-2">
                            <FileInput
                                id="course-cover"
                                :model-value="selectedCover"
                                accept="image/jpeg,image/png,image/webp"
                                button-label="Elegir imagen"
                                @update:model-value="onCoverSelected"
                            />
                            <Button v-if="currentCoverUrl" variant="secondary" @click="removeCover">
                                Quitar imagen
                            </Button>
                        </div>
                    </div>
                </FormField>
                <div class="flex flex-wrap gap-3">
                    <Button type="submit" :loading="isProcessing">
                        {{ course ? 'Guardar cambios' : 'Crear curso' }}
                    </Button>
                    <Link
                        :href="course ? `/courses/${course.id}` : '/courses'"
                        class="inline-flex min-h-11 items-center rounded-control border border-border bg-panel px-4 text-sm font-medium text-text transition hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    >
                        Cancelar
                    </Link>
                </div>
            </form>

            <Modal
                :open="isCategoryModalOpen"
                title="Crear nueva categoría"
                size="md"
                @close="closeCategoryModal"
            >
                <form id="inline-category-form" class="space-y-4" @submit.prevent="submitCategory">
                    <FormField
                        id="inline-category-name"
                        label="Nombre de la categoría"
                        :error="categoryNameError"
                    >
                        <Input
                            id="inline-category-name"
                            v-model="categoryName"
                            placeholder="Ej. Liderazgo"
                            :error="categoryNameError"
                        />
                    </FormField>

                    <FormField
                        id="inline-category-role"
                        label="Rol disponible para esta categoría"
                        :error="categoryRoleError"
                    >
                        <Select
                            id="inline-category-role"
                            v-model="categoryRoleName"
                            :options="roleOptions"
                        />
                    </FormField>
                </form>
                <template #actions>
                    <div class="flex justify-end gap-2">
                        <Button variant="secondary" @click="closeCategoryModal">Cancelar</Button>
                        <Button
                            type="submit"
                            form="inline-category-form"
                            :loading="isCreatingCategory"
                        >
                            Guardar categoría
                        </Button>
                    </div>
                </template>
            </Modal>
        </main>
    </AppLayout>
</template>
