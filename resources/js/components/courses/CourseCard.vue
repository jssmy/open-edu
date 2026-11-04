<script setup lang="ts">
import { computed } from 'vue';
import Badge from '@/design-system/Badge.vue';
import IconButton from '@/design-system/IconButton.vue';
import { useCourses } from '@/composables/useCourses';
import type { CourseStatus } from '@/services/courses';

export interface CourseCardItem {
    id: number;
    name: string;
    excerpt?: string | null;
    description?: string | null;
    cover_url: string;
    units_count?: number;
    status?: CourseStatus;
    teachers?: { id: number; name: string }[];
}

const props = defineProps<{
    course: CourseCardItem;
}>();

const { statusLabel, statusVariant } = useCourses();

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
            <img :src="course.cover_url" alt="" loading="lazy" class="size-full object-cover" />
            <div class="absolute right-2 top-2 flex flex-wrap justify-end gap-1.5">
                <slot name="badges">
                    <Badge v-if="course.status" :variant="statusVariant(course.status)">
                        {{ statusLabel(course.status) }}
                    </Badge>
                </slot>
            </div>
        </div>
        <div class="flex flex-1 flex-col gap-2 p-4">
            <div class="flex items-start justify-between gap-2">
                <h2 class="font-semibold text-text">{{ course.name }}</h2>
                <slot name="header-extra" />
            </div>
            <p class="line-clamp-3 flex-1 text-sm text-text-muted">
                {{ summaryText }}
            </p>
            <div class="space-y-1 text-xs text-text-muted">
                <p>{{ metadataLine }}</p>
                <slot name="meta" />
            </div>
            <div class="mt-1 flex items-center justify-between gap-2 border-t border-border/60 pt-3">
                <slot name="footer">
                    <span />
                    <IconButton
                        icon="view"
                        :label="`Ver curso ${course.name}`"
                        :href="`/courses/${course.id}`"
                        variant="primary"
                    />
                </slot>
            </div>
        </div>
    </article>
</template>
