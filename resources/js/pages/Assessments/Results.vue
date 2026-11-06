<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Badge from '@/design-system/Badge.vue';
import Card from '@/design-system/Card.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import RichTextContent from '@/components/editor/RichTextContent.vue';
import type { RichTextDocument } from '@/types/richText';

interface Result {
    assessment_id?: number;
    assessment_title?: string;
    student_name?: string;
    attempt_number: number;
    score: number;
    total_score: number;
    passed: boolean;
    submitted_at: string;
}

defineProps<{
    assessment: {
        id: number;
        title: string;
        total_score: number;
        instructions: RichTextDocument | null;
        questions: { id: number; statement: RichTextDocument }[];
    } | null;
    result: Result | null;
    results: Result[];
    hasInProgressAttempt?: boolean;
    canTake?: boolean;
}>();

function submittedAt(value: string): string {
    return new Date(value).toLocaleString();
}
</script>

<template>
    <Head :title="assessment ? `Resultados: ${assessment.title}` : 'Mis resultados'" />
    <AppLayout>
        <main class="mx-auto flex w-full max-w-4xl flex-col gap-6">
            <header class="flex flex-col gap-2">
                <Link
                    :href="assessment ? `/assessments/${assessment.id}/edit` : '/my/courses'"
                    class="text-sm text-primary hover:underline"
                >
                    {{ assessment ? 'Volver a la evaluación' : 'Volver a mis cursos' }}
                </Link>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h1 class="text-2xl font-semibold">
                        {{ assessment ? `Resultados: ${assessment.title}` : 'Mis resultados' }}
                    </h1>
                    <Link
                        v-if="assessment && result"
                        :href="`/assessments/${assessment.id}/review`"
                        class="inline-flex min-h-9 items-center justify-center rounded-control bg-primary px-4 py-1.5 text-xs font-semibold text-white hover:bg-primary/90 transition shadow-xs"
                    >
                        Ver respuestas y corrección detallada →
                    </Link>
                </div>
            </header>

            <!-- Alerta para examen en curso -->
            <Alert
                v-if="hasInProgressAttempt"
                variant="warning"
                title="Evaluación en curso"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm">
                        Tienes un intento activo en curso para este examen. Puedes reanudarlo para responder tus preguntas y finalizarlo.
                    </p>
                    <Link
                        v-if="assessment"
                        :href="`/assessments/${assessment.id}/take`"
                        class="inline-flex min-h-9 items-center justify-center rounded-control bg-primary px-4 py-1.5 text-xs font-semibold text-white hover:bg-primary/90 transition shadow-xs"
                    >
                        Continuar evaluación →
                    </Link>
                </div>
            </Alert>

            <!-- Alerta para examen aún disponible con intentos pendientes -->
            <Alert
                v-else-if="canTake && result"
                variant="info"
                title="Evaluación aún disponible"
            >
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm">
                        Aún dispones de intentos para rendir esta evaluación y mejorar tu calificación.
                    </p>
                    <Link
                        v-if="assessment"
                        :href="`/assessments/${assessment.id}/take`"
                        class="inline-flex min-h-9 items-center justify-center rounded-control bg-primary px-4 py-1.5 text-xs font-semibold text-white hover:bg-primary/90 transition shadow-xs"
                    >
                        Rendir nuevo intento →
                    </Link>
                </div>
            </Alert>

            <section
                v-if="assessment"
                class="flex flex-col gap-4"
                aria-labelledby="assessment-content-heading"
            >
                <h2 id="assessment-content-heading" class="text-lg font-semibold">
                    Contenido de la evaluación
                </h2>
                <RichTextContent
                    v-if="assessment.instructions"
                    :content="assessment.instructions"
                    label="Instrucciones de la evaluación"
                />
                <article
                    v-for="(question, index) in assessment.questions"
                    :key="question.id"
                    class="flex flex-col gap-2 rounded-card border border-border bg-panel p-4"
                >
                    <h3 class="font-semibold">Pregunta {{ index + 1 }}</h3>
                    <RichTextContent
                        :content="question.statement"
                        :label="`Enunciado de la pregunta ${index + 1}`"
                    />
                </article>
            </section>

            <Alert v-if="result" :variant="result.passed ? 'success' : 'warning'">
                <span class="font-semibold">{{ result.passed ? 'Aprobado' : 'No aprobado' }}</span>
                · {{ result.score }} de {{ result.total_score }} puntos · Intento
                {{ result.attempt_number }} · {{ submittedAt(result.submitted_at) }}
            </Alert>

            <EmptyState
                v-if="assessment && result === null && results.length === 0"
                title="Aún no hay resultados para esta evaluación"
                description="Completa la evaluación para consultar tu puntaje."
            >
                <Link :href="`/assessments/${assessment.id}/take`" class="font-medium text-primary underline">
                    Rendir evaluación
                </Link>
            </EmptyState>

            <EmptyState
                v-else-if="!assessment && results.length === 0"
                title="Aún no tienes resultados"
                description="Tus resultados aparecerán aquí al completar evaluaciones."
            />

            <Card v-if="results.length > 0" class="overflow-x-auto p-0">
                <table class="w-full border-collapse text-left text-sm">
                    <caption class="sr-only">
                        Resultados de evaluaciones
                    </caption>
                    <thead class="bg-surface text-text-muted">
                        <tr>
                            <th scope="col" class="p-3 font-medium">Evaluación</th>
                            <th v-if="results[0]?.student_name" scope="col" class="p-3 font-medium">
                                Estudiante
                            </th>
                            <th scope="col" class="p-3 font-medium">Puntaje</th>
                            <th scope="col" class="p-3 font-medium">Estado</th>
                            <th scope="col" class="p-3 font-medium">Intento</th>
                            <th scope="col" class="p-3 font-medium">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in results" :key="`${item.assessment_id ?? index}-${index}`">
                            <th scope="row" class="border-t border-border p-3 font-medium">
                                {{ item.assessment_title ?? assessment?.title }}
                            </th>
                            <td v-if="item.student_name" class="border-t border-border p-3">
                                {{ item.student_name }}
                            </td>
                            <td class="border-t border-border p-3">
                                {{ item.score }} / {{ item.total_score }}
                            </td>
                            <td class="border-t border-border p-3">
                                <Badge :variant="item.passed ? 'success' : 'danger'">
                                    {{ item.passed ? 'Aprobado' : 'No aprobado' }}
                                </Badge>
                            </td>
                            <td class="border-t border-border p-3">{{ item.attempt_number }}</td>
                            <td class="border-t border-border p-3">{{ submittedAt(item.submitted_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </Card>
        </main>
    </AppLayout>
</template>
