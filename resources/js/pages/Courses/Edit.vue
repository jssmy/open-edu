<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Button from '@/design-system/Button.vue';
import FormField from '@/design-system/FormField.vue';
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
    lock_version: number;
}

const props = defineProps<{ course: Course | null }>();
const form = useForm({
    name: props.course?.name ?? '',
    description: props.course?.description ?? emptyRichTextDocument(),
    objectives: props.course?.objectives ?? emptyRichTextDocument(),
    intro_video_url: props.course?.intro_video_url ?? '',
    lock_version: props.course?.lock_version ?? null,
});
const requestError = ref('');
const isSubmitting = ref(false);
const introVideoUrl = computed({
    get: () => form.intro_video_url,
    set: (url: string) => {
        form.intro_video_url = url;
    },
});
const introVideoUrlError = computed(() => form.errors.intro_video_url);
const isProcessing = computed(() => form.processing);

useUnsavedChangesGuard(() => form.isDirty && !isSubmitting.value);

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
            ...data,
            lock_version: lock_version ?? 0,
        }));
        form.put(`/courses/${props.course.id}`, options);
    } else {
        form.transform((data) => {
            const payload = { ...data };
            Reflect.deleteProperty(payload, 'lock_version');

            return payload;
        });
        form.post('/courses', options);
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
