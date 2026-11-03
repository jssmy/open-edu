<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Button from '@/design-system/Button.vue';
import Card from '@/design-system/Card.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import RichTextContent from '@/components/editor/RichTextContent.vue';
import type { RichTextDocument } from '@/types/richText';

interface Choice {
    id: number;
    text: string;
}

interface Question {
    id: number;
    statement: RichTextDocument;
    points: number;
    choices: Choice[];
}

const props = defineProps<{
    assessment: {
        id: number;
        title: string;
        instructions: RichTextDocument | null;
        total_score: number;
        attempt_number: number;
        questions: Question[];
    };
}>();

const form = useForm<{ answers: Record<number, number> }>({ answers: {} });

function isComplete(): boolean {
    return props.assessment.questions.every((question) => form.answers[question.id] !== undefined);
}

function submitAttempt() {
    form.post(`/assessments/${props.assessment.id}/attempts`, { preserveScroll: true });
}
</script>

<template>
    <Head :title="assessment.title" />
    <AppLayout>
        <main class="mx-auto flex w-full max-w-3xl flex-col gap-6">
            <header class="flex flex-col gap-2">
                <Link href="/my/courses" class="text-sm text-primary hover:underline"
                    >Volver a mis cursos</Link
                >
                <h1 class="text-2xl font-semibold">{{ assessment.title }}</h1>
                <p class="text-sm text-text-muted">
                    Intento {{ assessment.attempt_number }} · Puntaje total: {{ assessment.total_score }}
                </p>
                <RichTextContent
                    v-if="assessment.instructions"
                    :content="assessment.instructions"
                    label="Instrucciones de la evaluación"
                />
            </header>

            <Alert v-if="form.errors.answers" variant="danger" role="alert">
                {{ form.errors.answers }}
            </Alert>

            <EmptyState
                v-if="assessment.questions.length === 0"
                title="Esta evaluación no tiene preguntas disponibles"
                description="Vuelve al curso o contacta a tu docente."
            />
            <form v-else class="flex flex-col gap-5" @submit.prevent="submitAttempt">
                <Card
                    v-for="(question, questionIndex) in assessment.questions"
                    :key="question.id"
                    class="flex flex-col gap-4"
                >
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex min-w-0 flex-col gap-2">
                            <h2 class="font-semibold">Pregunta {{ questionIndex + 1 }}</h2>
                            <RichTextContent
                                :content="question.statement"
                                :label="`Enunciado de la pregunta ${questionIndex + 1}`"
                            />
                        </div>
                        <span class="text-sm text-text-muted">{{ question.points }} puntos</span>
                    </div>
                    <fieldset class="flex flex-col gap-3">
                        <legend class="sr-only">Alternativas para la pregunta {{ questionIndex + 1 }}</legend>
                        <label
                            v-for="choice in question.choices"
                            :key="choice.id"
                            class="flex cursor-pointer items-start gap-3 rounded-control border border-border p-3"
                        >
                            <input
                                v-model="form.answers[question.id]"
                                type="radio"
                                :name="`question-${question.id}`"
                                :value="choice.id"
                            />
                            <span>{{ choice.text }}</span>
                        </label>
                    </fieldset>
                </Card>
                <div class="flex flex-wrap items-center gap-3">
                    <Button type="submit" :loading="form.processing" :disabled="!isComplete()">
                        Enviar evaluación
                    </Button>
                    <span v-if="form.processing" class="text-sm text-text-muted" role="status">
                        Enviando respuestas…
                    </span>
                </div>
            </form>
        </main>
    </AppLayout>
</template>
