<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Button from '@/design-system/Button.vue';
import FormField from '@/design-system/FormField.vue';
import Input from '@/design-system/Input.vue';
import Select from '@/design-system/Select.vue';
import RichTextEditor from '@/components/editor/RichTextEditor.vue';
import { useUnsavedChangesGuard } from '@/composables/useUnsavedChangesGuard';
import { emptyRichTextDocument, type RichTextDocument } from '@/types/richText';

type LessonContentType = 'text' | 'document' | 'image' | 'link' | 'video';

interface Lesson {
    id: number;
    title: string;
    position: number;
    content_type: LessonContentType;
    content: RichTextDocument | string | null;
    lock_version: number;
}

const props = defineProps<{
    unit: { id: number; title: string; course_id: number; course_name: string };
    lesson: Lesson | null;
    nextPosition: number;
}>();
const form = useForm({
    title: props.lesson?.title ?? '',
    position: props.lesson?.position ?? props.nextPosition,
    content_type: props.lesson?.content_type ?? ('text' as LessonContentType),
    content: props.lesson?.content ?? emptyRichTextDocument(),
    file: null as globalThis.File | null,
    lock_version: props.lesson?.lock_version ?? null,
});
const requestError = ref('');
const isSubmitting = ref(false);
const contentOptions = [
    { value: 'text', label: 'Texto' },
    { value: 'link', label: 'Enlace' },
    { value: 'document', label: 'Documento' },
    { value: 'image', label: 'Imagen' },
    { value: 'video', label: 'Video' },
];
const textContent = computed<RichTextDocument>({
    get: () =>
        form.content !== null && typeof form.content === 'object' && form.content.type === 'doc'
            ? form.content
            : emptyRichTextDocument(),
    set: (document) => {
        form.content = document;
    },
});
const plainContent = computed<string>({
    get: () => (typeof form.content === 'string' ? form.content : ''),
    set: (content) => {
        form.content = content;
    },
});
const fileError = computed(() => form.errors.file);
const lockVersionError = computed(() => form.errors.lock_version);
const fileLabel = computed(() => (form.content_type === 'image' ? 'Imagen' : 'Documento'));
const fileAccept = computed(() =>
    form.content_type === 'image' ? 'image/jpeg,image/png,image/webp' : '.pdf,.doc,.docx,.ppt,.pptx',
);
const isFileRequired = computed(
    () =>
        !props.lesson ||
        props.lesson.content_type !== form.content_type ||
        typeof props.lesson.content !== 'string',
);
const isProcessing = computed(() => form.processing);

useUnsavedChangesGuard(() => form.isDirty && !isSubmitting.value);

watch(
    () => form.content_type,
    (contentType, previousType) => {
        if (contentType === previousType) {
            return;
        }

        form.content = contentType === 'text' ? emptyRichTextDocument() : '';
        form.file = null;
    },
);

function saveLesson(): void {
    requestError.value = '';
    isSubmitting.value = true;

    const options = {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            isSubmitting.value = false;
        },
        onHttpException: (response: { status: number }) => {
            if (response.status !== 409) {
                return;
            }

            requestError.value =
                'Esta lección fue actualizada por otra persona. Tus cambios siguen aquí; copia tu contenido y recarga para revisar la versión más reciente.';

            return false;
        },
    };

    if (props.lesson) {
        form.transform(({ lock_version, ...data }) => ({
            ...data,
            lock_version: lock_version ?? 0,
        }));
        form.put(`/lessons/${props.lesson.id}`, options);
    } else {
        form.transform((data) => {
            const payload = { ...data };
            Reflect.deleteProperty(payload, 'lock_version');

            return payload;
        });
        form.post(`/units/${props.unit.id}/lessons`, options);
    }
}

function selectFile(event: globalThis.Event): void {
    const target = event.target;
    form.file = target instanceof globalThis.HTMLInputElement ? (target.files?.[0] ?? null) : null;
}
</script>

<template>
    <Head :title="lesson ? 'Editar lección' : 'Nueva lección'" />
    <AppLayout>
        <main class="mx-auto flex w-full max-w-4xl flex-col gap-6">
            <header class="flex flex-col gap-2">
                <Link :href="`/courses/${unit.course_id}`" class="w-fit text-sm text-primary hover:underline">
                    Volver al curso
                </Link>
                <h1 class="text-2xl font-semibold">{{ lesson ? 'Editar lección' : 'Nueva lección' }}</h1>
                <p class="text-sm text-text-muted">{{ unit.course_name }} · {{ unit.title }}</p>
            </header>

            <Alert v-if="requestError" variant="danger">{{ requestError }}</Alert>

            <form
                class="flex flex-col gap-5 rounded-card border border-border bg-panel p-5"
                @submit.prevent="saveLesson"
            >
                <FormField id="lesson-title" label="Título" :error="form.errors.title">
                    <Input id="lesson-title" v-model="form.title" :error="form.errors.title" />
                </FormField>
                <FormField id="lesson-position" label="Posición" :error="form.errors.position">
                    <Input
                        id="lesson-position"
                        v-model.number="form.position"
                        type="number"
                        min="1"
                        :error="form.errors.position"
                    />
                </FormField>
                <FormField id="lesson-type" label="Tipo de contenido" :error="form.errors.content_type">
                    <Select id="lesson-type" v-model="form.content_type" :options="contentOptions" />
                </FormField>
                <FormField
                    v-if="form.content_type === 'text'"
                    id="lesson-text-content"
                    label="Contenido"
                    :error="form.errors.content"
                >
                    <RichTextEditor
                        id="lesson-text-content"
                        v-model="textContent"
                        label="Contenido de la lección"
                        :course-id="unit.course_id"
                        :error="form.errors.content"
                    />
                </FormField>
                <FormField
                    v-else-if="form.content_type === 'link'"
                    id="lesson-link"
                    label="Enlace"
                    :error="form.errors.content"
                >
                    <Input id="lesson-link" v-model="plainContent" type="url" :error="form.errors.content" />
                </FormField>
                <FormField
                    v-else-if="form.content_type === 'video'"
                    id="lesson-video"
                    label="URL del video (HTTPS)"
                    :error="form.errors.content"
                >
                    <Input
                        id="lesson-video"
                        v-model="plainContent"
                        type="url"
                        placeholder="https://"
                        :error="form.errors.content"
                    />
                </FormField>
                <FormField v-else id="lesson-file" :label="fileLabel" :error="fileError">
                    <p
                        v-if="lesson && typeof lesson.content === 'string'"
                        class="mb-2 text-sm text-text-muted"
                    >
                        Archivo actual: {{ lesson.content.split('/').at(-1) }}
                    </p>
                    <input
                        id="lesson-file"
                        type="file"
                        class="block w-full min-w-0 text-sm"
                        :accept="fileAccept"
                        :required="isFileRequired"
                        @change="selectFile"
                    />
                    <p class="mt-1 text-sm text-text-muted">
                        Seleccionar un archivo nuevo reemplaza el actual.
                    </p>
                </FormField>
                <p v-if="lockVersionError" class="text-sm text-danger" role="alert">
                    {{ lockVersionError }}
                </p>
                <div class="flex flex-wrap gap-3">
                    <Button type="submit" :loading="isProcessing">
                        {{ lesson ? 'Guardar cambios' : 'Crear lección' }}
                    </Button>
                    <Link
                        :href="`/courses/${unit.course_id}`"
                        class="inline-flex min-h-11 items-center rounded-control border border-border bg-panel px-4 text-sm font-medium text-text transition hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    >
                        Cancelar
                    </Link>
                </div>
            </form>
        </main>
    </AppLayout>
</template>
