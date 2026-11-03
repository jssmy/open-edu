<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Alert from '@/design-system/Alert.vue';
import Badge from '@/design-system/Badge.vue';
import Card from '@/design-system/Card.vue';
import EmptyState from '@/design-system/EmptyState.vue';

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
    assessment: { id: number; title: string; total_score: number } | null;
    result: Result | null;
    results: Result[];
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
                <h1 class="text-2xl font-semibold">
                    {{ assessment ? `Resultados: ${assessment.title}` : 'Mis resultados' }}
                </h1>
            </header>

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
