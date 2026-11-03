<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Button from '@/design-system/Button.vue';
import FormField from '@/design-system/FormField.vue';
import Input from '@/design-system/Input.vue';
import RichTextEditor from '@/components/editor/RichTextEditor.vue';
import { useUnsavedChangesGuard } from '@/composables/useUnsavedChangesGuard';
import { emptyRichTextDocument, type RichTextDocument } from '@/types/richText';

interface Choice {
    id?: number;
    text: string;
    is_correct: boolean;
}

interface Question {
    id?: number;
    statement: RichTextDocument;
    points: number;
    choices: Choice[];
}

interface Assessment {
    id: number;
    title: string;
    instructions: RichTextDocument | null;
    max_attempts: number;
    passing_score: number;
    is_published: boolean;
    lock_version: number;
    questions: Question[];
}

const props = defineProps<{
    course: { id: number; name: string };
    assessment: Assessment | null;
}>();

function emptyQuestion(): Question {
    return {
        statement: emptyRichTextDocument(),
        points: 1,
        choices: [
            { text: '', is_correct: true },
            { text: '', is_correct: false },
        ],
    };
}

const form = useForm({
    title: props.assessment?.title ?? '',
    instructions: props.assessment?.instructions ?? null,
    max_attempts: props.assessment?.max_attempts ?? 1,
    passing_score: props.assessment?.passing_score ?? 0,
    lock_version: props.assessment?.lock_version ?? null,
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
const isSubmitting = ref(false);

useUnsavedChangesGuard(() => form.isDirty && !isSubmitting.value);

function currentAssessmentVersion(): number | null {
    return props.assessment?.lock_version ?? null;
}

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
    isSubmitting.value = true;
    const isCreatingAssessment = props.assessment === null;
    const options = {
        preserveScroll: true,
        onFinish: () => {
            isSubmitting.value = false;
        },
        onSuccess: () => {
            if (isCreatingAssessment) {
                form.lock_version = currentAssessmentVersion();
            }

            form.defaults();
            statusMessage.value = isCreatingAssessment ? 'Evaluación guardada.' : 'Evaluación actualizada.';
        },
        onError: (errors: Record<string, string>) => {
            requestError.value = errors.questions ?? errors.general ?? 'Revisa los datos de la evaluación.';
        },
        onHttpException: (response: { status: number }) => {
            if (response.status !== 409) {
                return;
            }

            requestError.value =
                'Esta evaluación fue actualizada por otra persona. Tus cambios siguen aquí; copia tu contenido y recarga para revisar la versión más reciente.';

            return false;
        },
    };

    if (props.assessment) {
        form.transform(({ lock_version, ...data }) => ({
            ...data,
            lock_version: lock_version ?? 0,
        }));
        form.put(`/assessments/${props.assessment.id}`, options);
    } else {
        form.transform((data) => {
            const payload = { ...data };
            Reflect.deleteProperty(payload, 'lock_version');

            return payload;
        });
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
                form.lock_version = currentAssessmentVersion() ?? form.lock_version;
                form.defaults();
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
                    :disabled="form.processing || form.isDirty"
                    @click="publishAssessment"
                >
                    Publicar evaluación
                </Button>
                <p
                    v-if="assessment && !assessment.is_published && form.isDirty"
                    class="basis-full text-sm text-text-muted"
                >
                    Guarda los cambios antes de publicar la evaluación.
                </p>
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

                <FormField id="assessment-instructions" label="Instrucciones">
                    <RichTextEditor
                        id="assessment-instructions"
                        v-model="form.instructions"
                        label="Instrucciones de la evaluación"
                        :course-id="course.id"
                        :error="form.errors.instructions"
                    />
                </FormField>

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
                            <FormField :id="`question-${questionIndex}-statement`" label="Enunciado">
                                <RichTextEditor
                                    :id="`question-${questionIndex}-statement`"
                                    v-model="question.statement"
                                    :label="`Enunciado de la pregunta ${questionIndex + 1}`"
                                    :course-id="course.id"
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
