<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import ProgressBar from '@/components/progress/ProgressBar.vue';
import Badge from '@/design-system/Badge.vue';
import { useCourses } from '@/composables/useCourses';
import type { CourseStatus } from '@/services/courses';

export interface CourseCardProgress {
    completed_lessons: number;
    total_lessons: number;
    percentage: number;
}

export interface CourseCardItem {
    id: number;
    name: string;
    excerpt?: string | null;
    description?: string | null;
    cover_url: string;
    units_count?: number;
    status?: CourseStatus;
    category_id?: number | null;
    category_name?: string | null;
    category_role_name?: string | null;
    category?: { id: number; name: string; slug: string; role_name?: string } | null;
    target_audience?: 'student' | 'teacher';
    issues_certificate?: boolean;
    is_enrolled?: boolean;
    study_url?: string | null;
    progress?: CourseCardProgress | null;
    teachers?: { id: number; name: string }[];
}

const props = defineProps<{
    course: CourseCardItem;
    detailHref?: string;
}>();

const { statusLabel, statusVariant } = useCourses();

const targetHref = computed(
    () => props.detailHref ?? props.course.study_url ?? `/courses/${props.course.id}`,
);

const categoryLabel = computed(
    () => props.course.category_name ?? props.course.category?.name ?? null,
);

const summaryText = computed(() => {
    const text = props.course.excerpt ?? props.course.description ?? '';
    return text.trim() !== '' ? text : 'Sin descripción.';
});

const teachersLabel = computed(() => {
    if (!props.course.teachers || props.course.teachers.length === 0) {
        return 'Sin docentes';
    }
    return props.course.teachers.map((teacher) => teacher.name).join(', ');
});

const metadataLine = computed(() => {
    if (props.course.units_count === undefined) {
        return teachersLabel.value;
    }
    const unitLabel = props.course.units_count === 1 ? 'unidad' : 'unidades';
    return `${teachersLabel.value} · ${props.course.units_count} ${unitLabel}`;
});
</script>

<template>
    <article
        class="flex h-full flex-col overflow-hidden rounded-card border border-border bg-panel shadow-card transition hover:border-primary/40"
        :data-testid="`course-card-${course.id}`"
    >
        <div class="relative aspect-video bg-surface">
            <Link
                :href="targetHref"
                :aria-label="`Ver detalle del curso ${course.name}`"
                :data-testid="`course-card-cover-link-${course.id}`"
                class="block size-full overflow-hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            >
                <img
                    :src="course.cover_url"
                    :alt="course.name"
                    loading="lazy"
                    class="size-full object-cover transition duration-200 hover:scale-[1.02]"
                />
            </Link>
            <div v-if="categoryLabel" class="pointer-events-none absolute left-2 top-2 flex flex-wrap gap-1.5">
                <Badge variant="neutral">
                    {{ categoryLabel }}
                </Badge>
            </div>
            <div class="pointer-events-none absolute right-2 top-2 flex flex-wrap justify-end gap-1.5">
                <slot name="badges">
                    <Badge v-if="course.status" :variant="statusVariant(course.status)">
                        {{ statusLabel(course.status) }}
                    </Badge>
                </slot>
            </div>
        </div>
        <div class="flex flex-1 flex-col gap-2 p-4">
            <div class="flex items-start justify-between gap-2">
                <h2 class="font-semibold text-text">
                    <Link
                        :href="targetHref"
                        :data-testid="`course-card-title-link-${course.id}`"
                        class="transition hover:text-primary focus-visible:outline-none focus-visible:underline"
                    >
                        {{ course.name }}
                    </Link>
                </h2>
                <slot name="header-extra" />
            </div>
            <p class="line-clamp-3 flex-1 text-sm text-text-muted">
                {{ summaryText }}
            </p>
            <div class="space-y-1 text-xs text-text-muted">
                <p>{{ metadataLine }}</p>
                <p v-if="course.issues_certificate !== undefined" class="text-[11px] font-medium text-emerald-700 dark:text-emerald-400">
                    {{ course.issues_certificate ? '🎓 Incluye certificado al finalizar' : 'Sin certificado' }}
                </p>
                <slot name="meta">
                    <div v-if="course.progress" class="mt-2 space-y-1.5" :data-testid="`course-card-progress-${course.id}`">
                        <div class="flex items-center justify-between text-xs text-text-muted">
                            <span>Avance del curso</span>
                            <span class="font-semibold text-text">
                                {{ course.progress.completed_lessons }} / {{ course.progress.total_lessons }} ({{ course.progress.percentage }}%)
                            </span>
                        </div>
                        <ProgressBar :percentage="course.progress.percentage" />
                    </div>
                </slot>
            </div>
            <div
                v-if="$slots.footer"
                class="mt-1 flex items-center justify-between gap-2 border-t border-border/60 pt-3"
            >
                <slot name="footer" />
            </div>
        </div>
    </article>
</template>
