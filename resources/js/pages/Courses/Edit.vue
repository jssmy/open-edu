<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Button from '@/design-system/Button.vue';
import FormField from '@/design-system/FormField.vue';
import FileInput from '@/design-system/FileInput.vue';
import Input from '@/design-system/Input.vue';
import RichTextEditor from '@/components/editor/RichTextEditor.vue';
import { useUnsavedChangesGuard } from '@/composables/useUnsavedChangesGuard';
import { emptyRichTextDocument, type RichTextDocument } from '@/types/richText';

interface Course {
    id: number;
    name: string;
    description: RichTextDocument | null;
    objectives: RichTextDocument | null;
    intro_video_url: string | null;
    cover_url: string;
    has_custom_cover: boolean;
    lock_version: number;
}

const props = defineProps<{ course: Course | null }>();
const form = useForm({
    name: props.course?.name ?? '',
    description: props.course?.description ?? emptyRichTextDocument(),
    objectives: props.course?.objectives ?? emptyRichTextDocument(),
    intro_video_url: props.course?.intro_video_url ?? '',
    lock_version: props.course?.lock_version ?? null,
    cover_image: null as globalThis.File | null,
    remove_cover: false,
});
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
function encodeForMultipart<T extends { description: unknown; objectives: unknown }>(data: T) {
    return {
        ...data,
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
        </main>
    </AppLayout>
</template>
