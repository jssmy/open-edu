<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/design-system/Badge.vue';
import Button from '@/design-system/Button.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import CourseCard, { type CourseCardItem } from '@/components/courses/CourseCard.vue';
import type { CourseStatus } from '@/services/courses';

interface TeacherClass {
    id: number;
    group_code: string;
    modality: string;
    starts_on: string | null;
    ends_on: string | null;
    course: {
        id: number;
        title?: string;
        name?: string;
        excerpt?: string | null;
        description?: unknown;
        cover_url?: string | null;
        cover_image_url?: string | null;
        units_count?: number;
        lessons_count?: number;
        status?: CourseStatus;
        teachers?: { id: number; name: string }[];
    };
}

defineProps<{
    classes: TeacherClass[];
}>();

function modalityVariant(modality: string): 'neutral' | 'success' | 'warning' | 'danger' {
    const normalized = modality.toLowerCase();
    if (normalized.includes('virtual') || normalized.includes('remoto')) return 'success';
    if (normalized.includes('hibrid') || normalized.includes('híbrid')) return 'warning';
    return 'neutral';
}

function toCourseCardItem(item: TeacherClass): CourseCardItem {
    return {
        id: item.course.id,
        name: item.course.name ?? item.course.title ?? 'Curso sin título',
        excerpt: item.course.excerpt ?? (typeof item.course.description === 'string' ? item.course.description : null),
        cover_url: item.course.cover_url ?? item.course.cover_image_url ?? '',
        units_count: item.course.units_count,
        status: item.course.status,
        teachers: item.course.teachers,
    };
}
</script>

<template>
    <Head title="Mis Clases" />
    <AppLayout>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-text sm:text-3xl">Mis Clases Asignadas</h1>
                <p class="mt-1 text-sm text-text-muted">
                    Gestiona y proyecta las diapositivas de los cursos que tienes a tu cargo.
                </p>
            </div>
        </div>

        <div v-if="classes.length === 0" class="rounded-card border border-border bg-panel">
            <EmptyState
                title="No tienes clases programadas"
                description="Actualmente no cuentas con clases ni horarios asignados para dictar."
            />
        </div>

        <ul v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <li v-for="item in classes" :key="item.id" class="flex flex-col">
                <CourseCard :course="toCourseCardItem(item)">
                    <template #badges>
                        <Badge variant="neutral">
                            Grupo {{ item.group_code }}
                        </Badge>
                        <Badge :variant="modalityVariant(item.modality)">
                            {{ item.modality }}
                        </Badge>
                    </template>

                    <template #meta>
                        <div class="flex items-center gap-3 text-xs text-text-muted">
                            <span v-if="item.course.lessons_count !== undefined">
                                🎯 {{ item.course.lessons_count }} {{ item.course.lessons_count === 1 ? 'diapositiva' : 'diapositivas' }}
                            </span>
                            <span v-if="item.starts_on">
                                📅 Inicio: {{ item.starts_on }}
                            </span>
                        </div>
                    </template>

                    <template #footer>
                        <span class="text-xs text-text-muted">
                            <strong class="text-text">{{ item.group_code }}</strong> · {{ item.modality }}
                        </span>
                        <Link :href="`/my/classes/${item.id}/viewer`">
                            <Button size="sm" variant="primary">
                                Entrar a Clase
                            </Button>
                        </Link>
                    </template>
                </CourseCard>
            </li>
        </ul>
    </AppLayout>
</template>
