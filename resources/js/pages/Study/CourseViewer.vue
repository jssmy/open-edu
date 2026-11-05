<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import Card from '@/design-system/Card.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import ProgressBar from '@/components/progress/ProgressBar.vue';
import Spinner from '@/design-system/Spinner.vue';
import RichTextContent from '@/components/editor/RichTextContent.vue';
import type { RichTextDocument } from '@/types/richText';

interface Lesson {
    id: number;
    title: string;
    position: number;
    content_type: 'text' | 'document' | 'image' | 'link' | 'video';
    content: RichTextDocument | string | null;
    is_completed: boolean;
}

interface Unit {
    id: number;
    title: string;
    position: number;
    lessons: Lesson[];
}

interface Course {
    id: number;
    name: string;
    description: RichTextDocument | null;
    intro_video_embed_url: string | null;
    is_finished?: boolean;
    ends_on?: string | null;
    units: Unit[];
    progress: {
        completed_lessons: number;
        total_lessons: number;
        percentage: number;
    };
    assessments: { id: number; title: string; total_score: number }[];
}

interface FlattenedLesson extends Lesson {
    unitTitle: string;
    unitId: number;
    indexInCourse: number;
}

const props = withDefaults(
    defineProps<{
        course: Course;
        isTeacherView?: boolean;
        isReadOnly?: boolean;
        backUrl?: string;
    }>(),
    {
        isTeacherView: false,
        isReadOnly: false,
        backUrl: '/my/classes',
    },
);

const isFinishedCourse = computed(() => props.isReadOnly || props.course.is_finished);

const completingLessonId = ref<number | null>(null);
const completionError = ref<string | null>(null);
const isDrawerOpen = ref(false);

const flattenedLessons = computed<FlattenedLesson[]>(() => {
    const list: FlattenedLesson[] = [];
    let count = 0;
    for (const unit of props.course.units) {
        for (const lesson of unit.lessons) {
            list.push({
                ...lesson,
                unitTitle: unit.title,
                unitId: unit.id,
                indexInCourse: count,
            });
            count++;
        }
    }
    return list;
});

// Inicializar en la primera lección no completada (si es estudiante) o en la primera
const activeLessonIndex = ref(0);
if (!props.isTeacherView) {
    const firstIncomplete = flattenedLessons.value.findIndex((l) => !l.is_completed);
    if (firstIncomplete >= 0) {
        activeLessonIndex.value = firstIncomplete;
    }
}

const activeLesson = computed<FlattenedLesson | null>(() => {
    return flattenedLessons.value[activeLessonIndex.value] ?? null;
});

const canGoPrev = computed(() => activeLessonIndex.value > 0);
const canGoNext = computed(() => activeLessonIndex.value < flattenedLessons.value.length - 1);

function prevLesson() {
    if (canGoPrev.value) {
        activeLessonIndex.value--;
    }
}

function nextLesson() {
    if (canGoNext.value) {
        activeLessonIndex.value++;
    }
}

function selectLesson(index: number) {
    if (index >= 0 && index < flattenedLessons.value.length) {
        activeLessonIndex.value = index;
        isDrawerOpen.value = false;
    }
}

function handleKeydown(event: KeyboardEvent) {
    if (event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement) {
        return;
    }
    if (event.key === 'ArrowRight') {
        nextLesson();
    } else if (event.key === 'ArrowLeft') {
        prevLesson();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});

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

function externalUrl(content: Lesson['content']): string | null {
    if (typeof content !== 'string' || !globalThis.URL.canParse(content)) {
        return null;
    }

    const url = new globalThis.URL(content);
    return url.protocol === 'https:' || url.protocol === 'http:' ? url.href : null;
}

function externalVideoEmbedUrl(content: Lesson['content']): string | null {
    const href = externalUrl(content);
    if (href === null) {
        return null;
    }

    const url = new globalThis.URL(href);
    const host = url.hostname.replace(/^www\./, '');
    const youtubeId =
        host === 'youtu.be' ? url.pathname.slice(1) : host === 'youtube.com' ? url.searchParams.get('v') : null;
    if (youtubeId !== null && /^[A-Za-z0-9_-]{11}$/.test(youtubeId)) {
        return `https://www.youtube-nocookie.com/embed/${youtubeId}`;
    }

    const vimeoId = host === 'vimeo.com' ? /^\/(\d{6,12})\/?$/.exec(url.pathname)?.[1] : undefined;
    return vimeoId === undefined ? null : `https://player.vimeo.com/video/${vimeoId}`;
}

function isRichTextDocument(content: Lesson['content']): content is RichTextDocument {
    return content !== null && typeof content === 'object' && content.type === 'doc';
}

function isStoredContent(content: Lesson['content']): boolean {
    return typeof content === 'string' && content.startsWith('courses/');
}
</script>

<template>
    <Head :title="`${course.name} - Diapositiva`" />
    <AppLayout>
        <!-- Cabecera de Navegación del Visor -->
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 border-b border-border pb-4">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <Link :href="backUrl" class="text-sm font-medium text-primary hover:underline">
                        ← Volver a mis clases
                    </Link>
                    <Badge v-if="isTeacherView" variant="neutral">Modo Docente / Proyección</Badge>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-text">{{ course.name }}</h1>
            </div>

            <div class="flex items-center gap-3">
                <Button variant="secondary" @click="isDrawerOpen = !isDrawerOpen">
                    <span class="mr-1">📋</span> Temario ({{ flattenedLessons.length }} lecciones)
                </Button>
                <div v-if="!isTeacherView" class="hidden sm:flex flex-col items-end">
                    <span class="text-xs text-text-muted">Progreso</span>
                    <span class="text-sm font-bold text-primary">{{ course.progress.percentage }}%</span>
                </div>
            </div>
        </div>

        <!-- Banner de Modo Consulta para Cursos Finalizados -->
        <Alert v-if="isFinishedCourse" variant="warning" class="mb-4">
            ℹ️ Este curso finalizó {{ course.ends_on ? `el ${course.ends_on}` : '' }}. Se encuentra en <strong>modo de consulta</strong>: puedes revisar los materiales y contenidos, pero no es posible modificar tu avance ni rendir evaluaciones.
        </Alert>

        <Alert v-if="completionError" variant="danger" class="mb-4">{{ completionError }}</Alert>

        <EmptyState
            v-if="flattenedLessons.length === 0"
            title="Este curso aún no tiene lecciones disponibles"
            description="No hay diapositivas para proyectar en este momento."
        />

        <div v-else class="relative grid grid-cols-1 gap-6 lg:grid-cols-4">
            <!-- Diapositiva Principal (Área de Estudio / Presentación) -->
            <div class="flex flex-col gap-4 lg:col-span-3">
                <Card v-if="activeLesson" class="min-h-[500px] flex flex-col justify-between border-2 border-primary/20 shadow-sm">
                    <!-- Cabecera de la diapositiva -->
                    <div>
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-border pb-3">
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wider text-text-muted">
                                    {{ activeLesson.unitTitle }}
                                </span>
                                <h2 class="text-xl font-bold text-text sm:text-2xl">{{ activeLesson.title }}</h2>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge v-if="!isTeacherView" :variant="activeLesson.is_completed ? 'success' : 'neutral'">
                                    {{ activeLesson.is_completed ? 'Completada' : 'Pendiente' }}
                                </Badge>
                                <Badge variant="neutral">
                                    Diapositiva {{ activeLessonIndex + 1 }} / {{ flattenedLessons.length }}
                                </Badge>
                            </div>
                        </div>

                        <!-- Contenido interactivo de la diapositiva -->
                        <div class="prose max-w-none py-2 text-text">
                            <RichTextContent
                                v-if="activeLesson.content_type === 'text' && isRichTextDocument(activeLesson.content)"
                                :content="activeLesson.content"
                                :label="`Contenido de ${activeLesson.title}`"
                            />
                            <div v-else-if="activeLesson.content_type === 'image' && isStoredContent(activeLesson.content)" class="flex justify-center">
                                <img
                                    :src="storedContentUrl(activeLesson)"
                                    :alt="activeLesson.title"
                                    class="max-h-[550px] w-full rounded-card object-contain shadow-inner"
                                />
                            </div>
                            <div v-else-if="activeLesson.content_type === 'image' && externalUrl(activeLesson.content)" class="flex justify-center">
                                <img
                                    :src="externalUrl(activeLesson.content) ?? undefined"
                                    :alt="activeLesson.title"
                                    loading="lazy"
                                    referrerpolicy="no-referrer"
                                    class="max-h-[550px] w-full rounded-card object-contain shadow-inner"
                                />
                            </div>
                            <div v-else-if="activeLesson.content_type === 'document' && isStoredContent(activeLesson.content)" class="flex flex-col items-center justify-center p-8 text-center bg-surface-muted rounded-card">
                                <span class="text-4xl mb-2">📄</span>
                                <p class="text-sm text-text-muted mb-4">Esta diapositiva contiene un documento descargable.</p>
                                <a
                                    :href="storedContentUrl(activeLesson)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="rounded-control bg-primary px-5 py-2.5 font-medium text-white hover:bg-primary-hover shadow"
                                >
                                    Descargar o Ver Documento
                                </a>
                            </div>
                            <div v-else-if="activeLesson.content_type === 'video' && isStoredContent(activeLesson.content)">
                                <video
                                    :src="storedContentUrl(activeLesson)"
                                    controls
                                    class="w-full rounded-card shadow aspect-video"
                                >
                                    Tu navegador no soporta reproducción de video.
                                </video>
                            </div>
                            <div v-else-if="activeLesson.content_type === 'video' && externalVideoEmbedUrl(activeLesson.content)">
                                <iframe
                                    :src="externalVideoEmbedUrl(activeLesson.content) ?? undefined"
                                    :title="`Video: ${activeLesson.title}`"
                                    referrerpolicy="no-referrer"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowfullscreen
                                    loading="lazy"
                                    class="aspect-video w-full rounded-card border border-border shadow"
                                />
                            </div>
                            <div v-else-if="activeLesson.content_type === 'link' && externalUrl(activeLesson.content)" class="flex flex-col items-center justify-center p-8 text-center bg-surface-muted rounded-card">
                                <span class="text-4xl mb-2">🔗</span>
                                <p class="text-sm text-text-muted mb-4">Enlace complementario para esta diapositiva.</p>
                                <a
                                    :href="externalUrl(activeLesson.content) ?? undefined"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="rounded-control bg-primary px-5 py-2.5 font-medium text-white hover:bg-primary-hover shadow"
                                >
                                    Abrir recurso externo
                                </a>
                            </div>
                            <Alert v-else variant="warning">El contenido de esta diapositiva no está configurado.</Alert>
                        </div>
                    </div>

                    <!-- Barra de control y navegación de diapositivas -->
                    <div class="mt-8 flex flex-wrap items-center justify-between gap-4 border-t border-border pt-4">
                        <Button
                            variant="secondary"
                            :disabled="!canGoPrev"
                            @click="prevLesson"
                        >
                            ← Anterior
                        </Button>

                        <!-- Acción de completar lección (centro/separada de la navegación para evitar clics accidentales) -->
                        <div class="flex items-center justify-center">
                            <Button
                                v-if="!isTeacherView && !isFinishedCourse && !activeLesson.is_completed"
                                variant="secondary"
                                :disabled="completingLessonId !== null"
                                :loading="completingLessonId === activeLesson.id"
                                @click="completeLesson(activeLesson)"
                            >
                                ✓ Marcar completada
                            </Button>
                            <span v-else-if="!isTeacherView && activeLesson.is_completed" class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 border border-emerald-200 dark:bg-emerald-950/30 dark:border-emerald-800 dark:text-emerald-400">
                                ✓ Lección completada
                            </span>
                            <span v-else-if="!isTeacherView && isFinishedCourse" class="text-xs text-text-muted italic">
                                Modo lectura (Finalizado)
                            </span>
                        </div>

                        <!-- Navegación a siguiente lección -->
                        <div class="flex items-center">
                            <Button
                                variant="primary"
                                :disabled="!canGoNext"
                                @click="nextLesson"
                            >
                                Siguiente →
                            </Button>
                        </div>
                    </div>
                </Card>

                <!-- Barra de progreso general para el alumno -->
                <Card v-if="!isTeacherView" class="flex flex-col gap-2">
                    <div class="flex justify-between gap-3 text-xs font-medium text-text-muted">
                        <span>Progreso global del curso</span>
                        <span>{{ course.progress.completed_lessons }} de {{ course.progress.total_lessons }} completadas ({{ course.progress.percentage }}%)</span>
                    </div>
                    <ProgressBar :percentage="course.progress.percentage" />
                </Card>
            </div>

            <!-- Panel lateral / Temario de lecciones -->
            <div
                class="flex flex-col gap-4 rounded-card border border-border bg-surface p-4 shadow-sm"
                :class="{ 'fixed inset-y-0 right-0 z-50 w-80 bg-surface shadow-2xl p-6 overflow-y-auto': isDrawerOpen, 'hidden lg:flex': !isDrawerOpen }"
            >
                <div class="flex items-center justify-between border-b border-border pb-3">
                    <h3 class="font-bold text-text">Temario del Curso</h3>
                    <button
                        v-if="isDrawerOpen"
                        class="text-sm font-semibold text-text-muted hover:text-text lg:hidden"
                        @click="isDrawerOpen = false"
                    >
                        ✕ Cerrar
                    </button>
                </div>

                <div class="flex flex-col gap-4 overflow-y-auto pr-1">
                    <div v-for="unit in course.units" :key="unit.id" class="flex flex-col gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-primary">
                            {{ unit.title }}
                        </span>
                        <div class="flex flex-col gap-1">
                            <button
                                v-for="lesson in unit.lessons"
                                :key="lesson.id"
                                class="flex items-center justify-between rounded-control px-3 py-2 text-left text-sm transition-colors"
                                :class="[
                                    activeLesson?.id === lesson.id
                                        ? 'bg-primary text-white font-semibold'
                                        : 'text-text hover:bg-surface-hover'
                                ]"
                                @click="selectLesson(flattenedLessons.findIndex((l) => l.id === lesson.id))"
                            >
                                <span class="truncate pr-2">{{ lesson.title }}</span>
                                <span v-if="!isTeacherView && lesson.is_completed" class="text-xs" :class="activeLesson?.id === lesson.id ? 'text-white' : 'text-emerald-600'">
                                    ✓
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Evaluaciones del curso -->
                    <div v-if="course.assessments && course.assessments.length > 0" class="mt-4 flex flex-col gap-2 border-t border-border pt-4">
                        <span class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                            Evaluaciones ({{ course.assessments.length }})
                        </span>
                        <div class="flex flex-col gap-2">
                            <div
                                v-for="assessment in course.assessments"
                                :key="assessment.id"
                                class="flex flex-col gap-2.5 rounded-control p-3 bg-surface-muted/60 border border-border/60 text-xs"
                            >
                                <div class="flex flex-col gap-0.5">
                                    <span class="font-medium text-text text-sm leading-snug">{{ assessment.title }}</span>
                                    <span class="text-[11px] text-text-muted">{{ assessment.total_score }} puntos</span>
                                </div>
                                <div class="flex flex-col gap-1.5 w-full">
                                    <Link
                                        v-if="!isFinishedCourse"
                                        :href="`/assessments/${assessment.id}/take`"
                                        class="w-full inline-flex items-center justify-center rounded bg-primary px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-hover transition text-center shadow-xs"
                                    >
                                        Rendir
                                    </Link>
                                    <Link
                                        :href="`/assessments/${assessment.id}/results`"
                                        class="w-full inline-flex items-center justify-center rounded border border-border bg-panel px-3 py-1.5 text-xs font-medium text-text hover:bg-surface transition text-center shadow-xs"
                                    >
                                        Ver notas
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
