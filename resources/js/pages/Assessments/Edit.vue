<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Button from '@/design-system/Button.vue';
import FormField from '@/design-system/FormField.vue';
import Input from '@/design-system/Input.vue';
import Textarea from '@/design-system/Textarea.vue';

interface Choice {
    id?: number;
    text: string;
    is_correct: boolean;
}

interface Question {
    id?: number;
    statement: string;
    points: number;
    choices: Choice[];
}

interface Assessment {
    id: number;
    title: string;
    max_attempts: number;
    passing_score: number;
    is_published: boolean;
    questions: Question[];
}

const props = defineProps<{
    course: { id: number; name: string };
    assessment: Assessment | null;
}>();

function emptyQuestion(): Question {
    return {
        statement: '',
        points: 1,
        choices: [
            { text: '', is_correct: true },
            { text: '', is_correct: false },
        ],
    };
}

const form = useForm({
    title: props.assessment?.title ?? '',
    max_attempts: props.assessment?.max_attempts ?? 1,
    passing_score: props.assessment?.passing_score ?? 0,
    questions: props.assessment?.questions.map((question) => ({
        statement: question.statement,
        points: question.points,
        choices: question.choices.map((choice) => ({
            text: choice.text,
            is_correct: choice.is_correct,
        })),
    })) ?? [emptyQuestion()],
});
const statusMessage = ref('');
const requestError = ref('');

function fieldError(path: string): string | undefined {
    return Object.entries(form.errors).find(([key]) => key === path)?.[1];
}

function addQuestion() {
    form.questions.push(emptyQuestion());
}

function removeQuestion(questionIndex: number) {
    if (form.questions.length > 1) {
        form.questions.splice(questionIndex, 1);
    }
}

function addChoice(question: Question) {
    question.choices.push({ text: '', is_correct: false });
}

function removeChoice(question: Question, choiceIndex: number) {
    if (question.choices.length > 2) {
        question.choices.splice(choiceIndex, 1);
    }
}

function chooseCorrect(question: Question, choiceIndex: number) {
    question.choices.forEach((choice, index) => {
        choice.is_correct = index === choiceIndex;
    });
}

function saveAssessment() {
    requestError.value = '';
    statusMessage.value = '';
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            statusMessage.value = props.assessment ? 'Evaluación actualizada.' : 'Evaluación guardada.';
        },
        onError: (errors: Record<string, string>) => {
            requestError.value = errors.questions ?? errors.general ?? 'Revisa los datos de la evaluación.';
        },
    };

    if (props.assessment) {
        form.put(`/assessments/${props.assessment.id}`, options);
    } else {
        form.post(`/courses/${props.course.id}/assessments`, options);
    }
}

function publishAssessment() {
    if (!props.assessment) {
        return;
    }

    requestError.value = '';
    statusMessage.value = '';
    router.patch(
        `/assessments/${props.assessment.id}/publish`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                statusMessage.value = 'Evaluación publicada.';
            },
            onError: (errors) => {
                requestError.value =
                    errors.questions ?? errors.passing_score ?? 'No se pudo publicar la evaluación.';
            },
        },
    );
}
</script>

<template>
    <Head :title="assessment ? 'Editar evaluación' : 'Nueva evaluación'" />
    <AppLayout>
        <main class="mx-auto flex w-full max-w-4xl flex-col gap-6">
            <header class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex flex-col gap-1">
                    <Link :href="`/courses/${course.id}`" class="text-sm text-primary hover:underline">
                        Volver al curso
                    </Link>
                    <h1 class="text-2xl font-semibold">
                        {{ assessment ? 'Editar evaluación' : 'Nueva evaluación' }}
                    </h1>
                    <p class="text-sm text-text-muted">{{ course.name }}</p>
                </div>
                <Button
                    v-if="assessment && !assessment.is_published"
                    variant="secondary"
                    :disabled="form.processing"
                    @click="publishAssessment"
                >
                    Publicar evaluación
                </Button>
            </header>

            <Alert v-if="requestError" variant="danger" role="alert">{{ requestError }}</Alert>
            <Alert v-if="statusMessage" variant="success" role="status">{{ statusMessage }}</Alert>

            <form class="flex flex-col gap-6" @submit.prevent="saveAssessment">
                <section class="grid gap-4 rounded-card border border-border bg-panel p-5 md:grid-cols-3">
                    <FormField id="assessment-title" label="Título" :error="form.errors.title">
                        <Input id="assessment-title" v-model="form.title" :error="form.errors.title" />
                    </FormField>
                    <FormField
                        id="assessment-max-attempts"
                        label="Intentos permitidos"
                        :error="form.errors.max_attempts"
                    >
                        <Input
                            id="assessment-max-attempts"
                            v-model.number="form.max_attempts"
                            type="number"
                            :error="form.errors.max_attempts"
                        />
                    </FormField>
                    <FormField
                        id="assessment-passing-score"
                        label="Puntaje mínimo"
                        :error="form.errors.passing_score"
                    >
                        <Input
                            id="assessment-passing-score"
                            v-model.number="form.passing_score"
                            type="number"
                            :error="form.errors.passing_score"
                        />
                    </FormField>
                </section>

                <section class="flex flex-col gap-4" aria-labelledby="questions-heading">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 id="questions-heading" class="text-xl font-semibold">Preguntas</h2>
                        <Button type="button" variant="secondary" @click="addQuestion">
                            Agregar pregunta
                        </Button>
                    </div>
                    <article
                        v-for="(question, questionIndex) in form.questions"
                        :key="questionIndex"
                        class="flex flex-col gap-4 rounded-card border border-border bg-panel p-5"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-semibold">Pregunta {{ questionIndex + 1 }}</h3>
                            <Button
                                type="button"
                                variant="danger"
                                :disabled="form.questions.length === 1"
                                :aria-label="`Eliminar pregunta ${questionIndex + 1}`"
                                @click="removeQuestion(questionIndex)"
                            >
                                Eliminar
                            </Button>
                        </div>
                        <div class="grid gap-4 md:grid-cols-[1fr_10rem]">
                            <FormField
                                :id="`question-${questionIndex}-statement`"
                                label="Enunciado"
                                :error="fieldError(`questions.${questionIndex}.statement`)"
                            >
                                <Textarea
                                    :id="`question-${questionIndex}-statement`"
                                    v-model="question.statement"
                                    :error="fieldError(`questions.${questionIndex}.statement`)"
                                />
                            </FormField>
                            <FormField
                                :id="`question-${questionIndex}-points`"
                                label="Puntaje"
                                :error="fieldError(`questions.${questionIndex}.points`)"
                            >
                                <Input
                                    :id="`question-${questionIndex}-points`"
                                    v-model.number="question.points"
                                    type="number"
                                    :error="fieldError(`questions.${questionIndex}.points`)"
                                />
                            </FormField>
                        </div>

                        <fieldset class="flex flex-col gap-3">
                            <legend class="text-sm font-medium">Alternativas (selecciona la correcta)</legend>
                            <div
                                v-for="(choice, choiceIndex) in question.choices"
                                :key="choiceIndex"
                                class="grid items-start gap-3 sm:grid-cols-[auto_1fr_auto]"
                            >
                                <input
                                    :id="`question-${questionIndex}-choice-${choiceIndex}-correct`"
                                    type="radio"
                                    :name="`question-${questionIndex}-correct`"
                                    :checked="choice.is_correct"
                                    :aria-label="`Alternativa ${choiceIndex + 1} correcta`"
                                    @change="chooseCorrect(question, choiceIndex)"
                                />
                                <div class="flex flex-col gap-1">
                                    <FormField
                                        :id="`question-${questionIndex}-choice-${choiceIndex}`"
                                        :label="`Alternativa ${choiceIndex + 1}`"
                                        :error="
                                            fieldError(
                                                `questions.${questionIndex}.choices.${choiceIndex}.text`,
                                            )
                                        "
                                    >
                                        <Input
                                            :id="`question-${questionIndex}-choice-${choiceIndex}`"
                                            v-model="choice.text"
                                            :error="
                                                fieldError(
                                                    `questions.${questionIndex}.choices.${choiceIndex}.text`,
                                                )
                                            "
                                        />
                                    </FormField>
                                </div>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    :disabled="question.choices.length <= 2"
                                    :aria-label="`Eliminar alternativa ${choiceIndex + 1} de pregunta ${questionIndex + 1}`"
                                    @click="removeChoice(question, choiceIndex)"
                                >
                                    Quitar
                                </Button>
                            </div>
                            <p
                                v-if="fieldError(`questions.${questionIndex}.choices`)"
                                class="text-sm text-danger"
                                role="alert"
                            >
                                {{ fieldError(`questions.${questionIndex}.choices`) }}
                            </p>
                            <Button type="button" variant="secondary" @click="addChoice(question)">
                                Agregar alternativa
                            </Button>
                        </fieldset>
                    </article>
                </section>

                <div class="flex flex-wrap gap-3">
                    <Button type="submit" :loading="form.processing">
                        {{ assessment ? 'Guardar cambios' : 'Crear evaluación' }}
                    </Button>
                    <Button
                        v-if="assessment"
                        type="button"
                        variant="secondary"
                        @click="router.visit(`/assessments/${assessment.id}/results`)"
                    >
                        Ver resultados
                    </Button>
                </div>
            </form>
        </main>
    </AppLayout>
</template>
