<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Badge from '@/design-system/Badge.vue';
import EmptyState from '@/design-system/EmptyState.vue';
import IconButton from '@/design-system/IconButton.vue';
import Input from '@/design-system/Input.vue';
import Pagination from '@/design-system/Pagination.vue';
import Select from '@/design-system/Select.vue';
import { useCourses } from '@/composables/useCourses';
import { useListFilters } from '@/services/listFilters';

interface CourseRow {
    id: number;
    name: string;
    excerpt: string;
    cover_url: string;
    units_count: number;
    status: 'draft' | 'published' | 'deactivated';
    teachers: { id: number; name: string }[];
}

const props = defineProps<{
    courses: {
        data: CourseRow[];
        meta: { page: number; last_page: number; total: number; from: number; to: number };
    };
    filters: { q: string; status: string };
    canManageCourses: boolean;
}>();
const { statusLabel, statusVariant } = useCourses();

const { filters, goToPage, reset } = useListFilters('/courses', {
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
});
const hasFilters = computed(() => filters.q !== '' || filters.status !== '');
const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'draft', label: 'Borrador' },
    { value: 'published', label: 'Publicado' },
    { value: 'deactivated', label: 'Desactivado' },
];
</script>

<template>
    <Head title="Cursos" />
    <AppLayout>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-semibold">Cursos</h1>
            <Link
                v-if="canManageCourses"
                href="/courses/create"
                class="inline-flex min-h-11 items-center justify-center rounded-control bg-primary px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            >
                Nuevo curso
            </Link>
        </div>

        <form class="mb-4 flex flex-wrap items-end gap-3" role="search" @submit.prevent="goToPage(1)">
            <div class="min-w-56 flex-1">
                <label for="filter-q" class="mb-1 block text-xs text-text-muted">Buscar</label>
                <Input id="filter-q" v-model="filters.q" placeholder="Nombre del curso o docente" />
            </div>
            <div class="w-48">
                <label for="filter-status" class="mb-1 block text-xs text-text-muted">Estado</label>
                <Select id="filter-status" v-model="filters.status" :options="statusOptions" />
            </div>
            <IconButton icon="search" label="Buscar" type="submit" variant="primary" />
            <IconButton v-if="hasFilters" icon="clear" label="Limpiar filtros" @click="reset" />
        </form>

        <ul v-if="courses.data.length > 0" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <li
                v-for="course in courses.data"
                :key="course.id"
                class="flex flex-col overflow-hidden rounded-card border border-border bg-panel shadow-card"
            >
                <div class="relative aspect-video bg-surface">
                    <img :src="course.cover_url" alt="" loading="lazy" class="size-full object-cover" />
                    <Badge class="absolute right-2 top-2" :variant="statusVariant(course.status)">{{
                        statusLabel(course.status)
                    }}</Badge>
                </div>
                <div class="flex flex-1 flex-col gap-2 p-4">
                    <h2 class="font-semibold">{{ course.name }}</h2>
                    <p class="line-clamp-3 flex-1 text-sm text-text-muted">
                        {{ course.excerpt || 'Sin descripción.' }}
                    </p>
                    <p class="text-xs text-text-muted">
                        {{ course.teachers.map((teacher) => teacher.name).join(', ') || 'Sin docentes' }}
                        · {{ course.units_count }} {{ course.units_count === 1 ? 'unidad' : 'unidades' }}
                    </p>
                    <div class="flex justify-end">
                        <IconButton
                            icon="view"
                            :label="`Ver curso ${course.name}`"
                            :href="`/courses/${course.id}`"
                            variant="primary"
                        />
                    </div>
                </div>
            </li>
        </ul>
        <div v-else class="rounded-card border border-border bg-panel">
            <EmptyState
                :title="hasFilters ? 'Sin resultados' : 'Aún no hay cursos'"
                :description="
                    hasFilters ? 'Prueba con otros filtros.' : 'Los cursos disponibles aparecerán aquí.'
                "
            />
        </div>
        <Pagination
            class="mt-4"
            :page="courses.meta.page"
            :last-page="courses.meta.last_page"
            :total="courses.meta.total"
            :from="courses.meta.from"
            :to="courses.meta.to"
            @change="goToPage"
        />
    </AppLayout>
</template>
