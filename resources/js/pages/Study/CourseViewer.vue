<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import Card from '@/design-system/Card.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import ProgressBar from '@/components/progress/ProgressBar.vue';
import Spinner from '@/design-system/Spinner.vue';

interface Lesson {
    id: number;
    title: string;
    position: number;
    content_type: 'text' | 'document' | 'image' | 'link' | 'video';
    content: string | null;
    is_completed: boolean;
}

interface Course {
    id: number;
    name: string;
    description: string | null;
    units: {
        id: number;
        title: string;
        position: number;
        lessons: Lesson[];
    }[];
    progress: {
        completed_lessons: number;
        total_lessons: number;
        percentage: number;
    };
    assessments: { id: number; title: string; total_score: number }[];
}

defineProps<{ course: Course }>();
const completingLessonId = ref<number | null>(null);
const completionError = ref<string | null>(null);

function completeLesson(lesson: Lesson) {
    if (lesson.is_completed || completingLessonId.value !== null) {
        return;
    }

    completionError.value = null;
    completingLessonId.value = lesson.id;
    router.post(
        `/lessons/${lesson.id}/complete`,
        {},
        {
            preserveScroll: true,
            onError: (errors) => {
                completionError.value =
                    errors.general ?? 'No se pudo guardar el progreso. Inténtalo nuevamente.';
            },
            onFinish: () => {
                completingLessonId.value = null;
            },
        },
    );
}

function storedContentUrl(lesson: Lesson): string {
    return `/lessons/${lesson.id}/content`;
}

function externalUrl(content: string | null): string | null {
    if (content === null || !globalThis.URL.canParse(content)) {
        return null;
    }

    const url = new globalThis.URL(content);
    return url.protocol === 'https:' || url.protocol === 'http:' ? url.href : null;
}
</script>

<template>
    <Head :title="course.name" />
    <AppLayout>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-col gap-1">
                <Link href="/my/courses" class="text-sm text-primary hover:underline"
                    >Volver a mis cursos</Link
                >
                <Link href="/my/results" class="text-sm text-primary hover:underline"> Mis resultados </Link>
                <Link href="/my/progress" class="text-sm text-primary hover:underline"> Mi progreso </Link>
                <h1 class="text-2xl font-semibold">{{ course.name }}</h1>
                <p v-if="course.description" class="text-sm text-text-muted">{{ course.description }}</p>
            </div>
            <span class="text-sm text-text-muted">
                {{ course.progress.completed_lessons }} de {{ course.progress.total_lessons }} lecciones
            </span>
        </div>

        <Card class="mb-6 flex flex-col gap-2">
            <div class="flex justify-between gap-3 text-sm font-medium">
                <span>Progreso del curso</span>
                <span>{{ course.progress.percentage }}%</span>
            </div>
            <ProgressBar :percentage="course.progress.percentage" />
        </Card>

        <EmptyState
            v-if="course.units.length === 0 || course.progress.total_lessons === 0"
            title="Este curso aún no tiene lecciones disponibles"
            description="Vuelve más tarde para continuar tu aprendizaje."
        />
        <div v-else class="flex flex-col gap-5">
            <Alert v-if="completionError" variant="danger">{{ completionError }}</Alert>
            <section
                v-for="unit in course.units"
                :key="unit.id"
                class="flex flex-col gap-4"
                :aria-labelledby="`unit-${unit.id}`"
            >
                <h2 :id="`unit-${unit.id}`" class="text-xl font-semibold">{{ unit.title }}</h2>
                <Card v-for="lesson in unit.lessons" :key="lesson.id" class="flex flex-col gap-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="font-semibold">{{ lesson.title }}</h3>
                        <Badge :variant="lesson.is_completed ? 'success' : 'neutral'">
                            {{ lesson.is_completed ? 'Completada' : 'Pendiente' }}
                        </Badge>
                    </div>

                    <p v-if="lesson.content_type === 'text' && lesson.content" class="whitespace-pre-wrap">
                        {{ lesson.content }}
                    </p>
                    <img
                        v-else-if="lesson.content_type === 'image' && lesson.content?.startsWith('courses/')"
                        :src="storedContentUrl(lesson)"
                        :alt="lesson.title"
                        class="max-h-[32rem] w-full rounded-card object-contain"
                    />
                    <a
                        v-else-if="
                            lesson.content_type === 'document' && lesson.content?.startsWith('courses/')
                        "
                        :href="storedContentUrl(lesson)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium text-primary underline"
                    >
                        Abrir documento
                    </a>
                    <video
                        v-else-if="lesson.content_type === 'video' && lesson.content?.startsWith('courses/')"
                        :src="storedContentUrl(lesson)"
                        controls
                        class="w-full rounded-card"
                    >
                        Tu navegador no puede reproducir este video.
                    </video>
                    <a
                        v-else-if="lesson.content_type === 'link' && externalUrl(lesson.content)"
                        :href="externalUrl(lesson.content) ?? undefined"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium text-primary underline"
                    >
                        Abrir recurso externo
                    </a>
                    <a
                        v-else-if="lesson.content_type === 'video' && externalUrl(lesson.content)"
                        :href="externalUrl(lesson.content) ?? undefined"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium text-primary underline"
                    >
                        Abrir video
                    </a>
                    <Alert v-else variant="warning"> El contenido de esta lección no está disponible. </Alert>

                    <div class="flex items-center gap-3">
                        <Button
                            :disabled="lesson.is_completed || completingLessonId !== null"
                            :loading="completingLessonId === lesson.id"
                            @click="completeLesson(lesson)"
                        >
                            {{ lesson.is_completed ? 'Lección completada' : 'Marcar como completada' }}
                        </Button>
                        <Spinner v-if="completingLessonId === lesson.id" />
                    </div>
                </Card>
            </section>
        </div>
        <section
            v-if="course.assessments.length > 0"
            class="mt-6 flex flex-col gap-3"
            aria-labelledby="course-assessments-heading"
        >
            <h2 id="course-assessments-heading" class="text-xl font-semibold">Evaluaciones</h2>
            <Card
                v-for="assessment in course.assessments"
                :key="assessment.id"
                class="flex flex-wrap items-center justify-between gap-3"
            >
                <div class="flex flex-col gap-1">
                    <h3 class="font-medium">{{ assessment.title }}</h3>
                    <span class="text-sm text-text-muted"> Puntaje total: {{ assessment.total_score }} </span>
                </div>
                <Link
                    :href="`/assessments/${assessment.id}/take`"
                    class="rounded-control bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-hover focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                >
                    Rendir evaluación
                </Link>
            </Card>
        </section>
    </AppLayout>
</template>
