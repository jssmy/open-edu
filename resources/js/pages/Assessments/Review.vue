<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import Card from '@/design-system/Card.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import RichTextContent from '@/components/editor/RichTextContent.vue';
import type { RichTextDocument } from '@/types/richText';

interface ChoiceItem {
    id: number;
    text: string;
    is_correct?: boolean | null;
}

interface QuestionItem {
    id: number;
    statement: RichTextDocument;
    points: number;
    choices: ChoiceItem[];
    student_answer: {
        choice_id: number | null;
        is_correct?: boolean | null;
    } | null;
}

interface AttemptItem {
    id: number;
    attempt_number: number;
    status: 'in_progress' | 'completed' | 'submitted' | 'abandoned';
    score: number;
    passed: boolean;
    started_at: string;
    submitted_at: string | null;
}

const props = defineProps<{
    assessment: {
        id: number;
        title: string;
        instructions: RichTextDocument | null;
        total_score: number;
        passing_score: number;
        course_name?: string | null;
    };
    student: {
        id: number;
        name: string;
        email?: string;
    } | null;
    attempt: AttemptItem | null;
    questions: QuestionItem[];
    isStudent: boolean;
    canContinue: boolean;
}>();

const isCompleted = computed(() => props.attempt?.status === 'completed' || props.attempt?.status === 'submitted');
const isInProgress = computed(() => props.attempt?.status === 'in_progress');

function formatDate(iso: string | null | undefined): string {
    if (!iso) {
        return '—';
    }
    return new Date(iso).toLocaleString();
}
</script>

<template>
    <Head :title="`Revisión: ${assessment.title}`" />
    <AppLayout>
        <main class="mx-auto flex w-full max-w-4xl flex-col gap-6" data-testid="assessment-review-page">
            <!-- Encabezado y Navegación de Regreso -->
            <header class="flex flex-col gap-2">
                <div class="flex items-center gap-2">
                    <Link
                        v-if="student && !isStudent"
                        :href="`/students/${student.id}`"
                        class="text-sm font-medium text-primary hover:underline"
                    >
                        ← Volver al perfil de {{ student.name }}
                    </Link>
                    <Link
                        v-else
                        href="/my/classes"
                        class="text-sm font-medium text-primary hover:underline"
                    >
                        ← Volver a mis clases
                    </Link>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <span v-if="assessment.course_name" class="text-xs font-semibold uppercase tracking-wider text-text-muted">
                            {{ assessment.course_name }}
                        </span>
                        <h1 class="text-2xl font-bold text-text">{{ assessment.title }}</h1>
                        <p v-if="student && !isStudent" class="text-sm text-text-muted">
                            Evaluación rendida por <strong class="text-text">{{ student.name }}</strong>
                        </p>
                    </div>

                    <div v-if="attempt" class="flex flex-wrap items-center gap-2">
                        <Badge :variant="isCompleted ? (attempt.passed ? 'success' : 'danger') : 'warning'">
                            {{
                                isCompleted
                                    ? attempt.passed
                                        ? `Aprobado (${attempt.score} / ${assessment.total_score})`
                                        : `Desaprobado (${attempt.score} / ${assessment.total_score})`
                                    : 'En curso'
                            }}
                        </Badge>
                        <Badge variant="neutral">Intento {{ attempt.attempt_number }}</Badge>
                    </div>
                </div>
            </header>

            <!-- Banner para estudiante con evaluación en curso -->
            <Alert
                v-if="canContinue"
                variant="warning"
                title="Evaluación en curso"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm">
                        Tienes un intento activo para esta evaluación iniciado el {{ formatDate(attempt?.started_at) }}. Puedes reanudarlo para responder y finalizar tu examen.
                    </p>
                    <Link
                        :href="`/assessments/${assessment.id}/take`"
                        class="inline-flex min-h-9 items-center justify-center rounded-control bg-primary px-4 py-1.5 text-xs font-semibold text-white hover:bg-primary/90 transition shadow-sm"
                        data-testid="continue-assessment-button"
                    >
                        Continuar evaluación →
                    </Link>
                </div>
            </Alert>

            <!-- Estado de evaluación no iniciada por el estudiante -->
            <EmptyState
                v-if="!attempt"
                title="Evaluación aún no iniciada"
                description="El estudiante no cuenta con intentos registrados para esta evaluación."
            >
                <Link
                    v-if="isStudent"
                    :href="`/assessments/${assessment.id}/take`"
                    class="font-medium text-primary underline"
                >
                    Comenzar evaluación
                </Link>
            </EmptyState>

            <!-- Bloqueo de revisión cuando hay un intento en curso -->
            <EmptyState
                v-else-if="canContinue"
                title="Revisión no disponible mientras el examen esté en curso"
                description="Para garantizar la integridad de la evaluación, las preguntas y respuestas correctas se mostrarán una vez que finalices tu intento activo."
            >
                <Link
                    :href="`/assessments/${assessment.id}/take`"
                    class="inline-flex min-h-9 items-center justify-center rounded-control bg-primary px-4 py-2 text-xs font-semibold text-white hover:bg-primary/90 transition shadow-sm"
                >
                    Ir al examen en curso →
                </Link>
            </EmptyState>

            <template v-else>
                <!-- Resumen de Entrega e Instrucciones -->
                <Card v-if="assessment.instructions" class="flex flex-col gap-2">
                    <h2 class="text-sm font-semibold text-text">Instrucciones</h2>
                    <RichTextContent :content="assessment.instructions" label="Instrucciones de la evaluación" />
                </Card>

                <!-- Desglose de Preguntas y Respuestas en Solo Lectura -->
                <section class="flex flex-col gap-4" aria-labelledby="questions-review-heading">
                    <h2 id="questions-review-heading" class="text-lg font-semibold text-text">
                        Preguntas y respuestas registradas
                    </h2>

                    <article
                        v-for="(question, qIndex) in questions"
                        :key="question.id"
                        class="flex flex-col gap-3 rounded-card border border-border bg-panel p-5 shadow-sm"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border/70 pb-2">
                            <span class="text-sm font-semibold text-text">Pregunta {{ qIndex + 1 }}</span>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-text-muted">Valor: {{ question.points }} pts</span>
                                <template v-if="isCompleted">
                                    <Badge
                                        v-if="question.student_answer?.is_correct"
                                        variant="success"
                                    >
                                        +{{ question.points }} pts (Correcta)
                                    </Badge>
                                    <Badge
                                        v-else-if="question.student_answer?.choice_id !== null && question.student_answer?.choice_id !== undefined"
                                        variant="danger"
                                    >
                                        0 pts (Incorrecta)
                                    </Badge>
                                    <Badge v-else variant="neutral">Sin responder</Badge>
                                </template>
                            </div>
                        </div>

                        <!-- Enunciado de la pregunta -->
                        <RichTextContent
                            :content="question.statement"
                            :label="`Enunciado de la pregunta ${qIndex + 1}`"
                        />

                        <!-- Opciones de respuesta en modo solo lectura -->
                        <div class="mt-2 flex flex-col gap-2" role="radiogroup" :aria-label="`Opciones de la pregunta ${qIndex + 1}`">
                            <div
                                v-for="choice in question.choices"
                                :key="choice.id"
                                class="flex items-start gap-3 rounded-control border p-3 text-sm transition"
                                :class="[
                                    question.student_answer?.choice_id === choice.id
                                        ? 'border-primary bg-primary/5 font-medium'
                                        : 'border-border bg-surface/50 text-text',
                                    isCompleted && choice.is_correct
                                        ? 'ring-1 ring-emerald-500/50 bg-emerald-500/5'
                                        : ''
                                ]"
                            >
                                <span
                                    class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border text-[10px]"
                                    :class="
                                        question.student_answer?.choice_id === choice.id
                                            ? 'border-primary bg-primary text-white font-bold'
                                            : 'border-border bg-surface text-text-muted'
                                    "
                                >
                                    {{ question.student_answer?.choice_id === choice.id ? '✓' : '' }}
                                </span>

                                <div class="flex flex-1 flex-wrap items-center justify-between gap-2">
                                    <span>{{ choice.text }}</span>
                                    <div class="flex items-center gap-1.5 text-xs">
                                        <span
                                            v-if="question.student_answer?.choice_id === choice.id"
                                            class="font-semibold text-primary text-[11px]"
                                        >
                                            [Respuesta del alumno]
                                        </span>
                                        <Badge
                                            v-if="isCompleted && choice.is_correct"
                                            variant="success"
                                        >
                                            Respuesta correcta
                                        </Badge>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </section>
            </template>
        </main>
    </AppLayout>
</template>

